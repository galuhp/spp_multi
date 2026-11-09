package main

import (
	"context"
	"errors"
	"log/slog"
	"net/http"
	"os"
	"os/signal"
	"syscall"
	"time"

	"github.com/glebarez/sqlite"
	"gorm.io/driver/mysql"
	"gorm.io/gorm"

	"spp-backend/internal/cache"
	"spp-backend/internal/config"
	"spp-backend/internal/handler"
	"spp-backend/internal/license"
	"spp-backend/internal/middleware"
	"spp-backend/internal/model"
	"spp-backend/internal/router"
)

var (
	version = "dev"
	commit  = "unknown"
)

func main() {
	cfg, err := config.Load()
	if err != nil {
		slog.Error("konfigurasi tidak valid", "error", err)
		os.Exit(1)
	}

	setupLogger(cfg.LogLevel)

	slog.Info("memulai backend E-SPP", "version", version, "commit", commit, "env", cfg.AppEnv)

	db, err := openDatabase(cfg)
	if err != nil {
		slog.Error("gagal terhubung ke database", "error", err)
		os.Exit(1)
	}

	sqlDB, err := db.DB()
	if err != nil {
		slog.Error("gagal membaca koneksi database", "error", err)
		os.Exit(1)
	}
	defer sqlDB.Close()

	if cfg.AutoMigrate {
		if err := db.AutoMigrate(&model.AuditLog{}); err != nil {
			slog.Error("gagal menjalankan auto migrate", "error", err)
			os.Exit(1)
		}
	}

	store, closeStore, err := buildCache(cfg)
	if err != nil {
		slog.Error("gagal menyiapkan cache", "error", err)
		os.Exit(1)
	}
	defer closeStore()

	handlers := router.Handlers{
		Health: handler.Health,
		Ready:  handler.Ready(db, store),

		Login:            handler.NotImplemented,
		PublicReceipt:    handler.NotImplemented,
		StudentDashboard: handler.NotImplemented,
		StudentCard:      handler.NotImplemented,
		StudentReceipt:   handler.NotImplemented,
		WaliTunggakan:    handler.NotImplemented,
		WaliRekap:        handler.NotImplemented,
		CreatePayment:    handler.NotImplemented,
		GetPayment:       handler.NotImplemented,
		CreateExpense:    handler.NotImplemented,
		VoidPayment:      handler.NotImplemented,
		AdminUsers:       handler.NotImplemented,
		AdminTariffs:     handler.NotImplemented,
		ProReport:        handler.NotImplemented,
		ProExport:        handler.NotImplemented,
	}

	engine := router.New(router.Deps{
		JWTSecret:        cfg.JWTSecret,
		Cache:            store,
		License:          license.NewStaticChecker(cfg.AppTier, cfg.Features),
		DB:               db,
		Handlers:         handlers,
		TrustedProxies:   cfg.TrustedProxies,
		RateLimitLogin:   cfg.RateLimitLogin,
		RateLimitReceipt: cfg.RateLimitReceipt,
		RateLimitWindow:  time.Minute,
		IdempotencyTTL:   cfg.IdempotencyTTL,
	})

	server := &http.Server{
		Addr:              ":" + cfg.Port,
		Handler:           engine,
		ReadHeaderTimeout: cfg.ReadHeaderTimeout,
		ReadTimeout:       cfg.ReadTimeout,
		WriteTimeout:      cfg.WriteTimeout,
		IdleTimeout:       cfg.IdleTimeout,
		MaxHeaderBytes:    1 << 20,
	}

	serverErrors := make(chan error, 1)
	go func() {
		serverErrors <- server.ListenAndServe()
	}()

	slog.Info("server mendengarkan", "port", cfg.Port, "tier", cfg.AppTier, "cache", cfg.CacheDriver)

	quit := make(chan os.Signal, 1)
	signal.Notify(quit, syscall.SIGINT, syscall.SIGTERM)

	select {
	case err := <-serverErrors:
		if !errors.Is(err, http.ErrServerClosed) {
			slog.Error("server berhenti tidak normal", "error", err)
			os.Exit(1)
		}

		return
	case received := <-quit:
		slog.Info("sinyal berhenti diterima, mematikan server", "signal", received.String())
	}

	ctx, cancel := context.WithTimeout(context.Background(), cfg.ShutdownTimeout)
	defer cancel()

	if err := server.Shutdown(ctx); err != nil {
		slog.Error("gagal mematikan server dengan rapi", "error", err)
		_ = server.Close()
	}

	slog.Info("server berhenti dengan rapi")
}

func openDatabase(cfg *config.Config) (*gorm.DB, error) {
	var dialector gorm.Dialector

	switch cfg.DBDriver {
	case "sqlite":
		dialector = sqlite.Open(cfg.DBDSN)
	default:
		dialector = mysql.Open(cfg.DBDSN)
	}

	db, err := gorm.Open(dialector, &gorm.Config{
		TranslateError: true,
	})
	if err != nil {
		return nil, err
	}

	sqlDB, err := db.DB()
	if err != nil {
		return nil, err
	}

	if cfg.DBDriver == "sqlite" {
		// SQLite hanya mendukung satu penulis; batasi koneksi agar tidak
		// memicu "database is locked" saat audit logger berjalan paralel.
		sqlDB.SetMaxOpenConns(1)
		sqlDB.SetMaxIdleConns(1)
	} else {
		sqlDB.SetMaxOpenConns(25)
		sqlDB.SetMaxIdleConns(10)
		sqlDB.SetConnMaxLifetime(30 * time.Minute)
		sqlDB.SetConnMaxIdleTime(5 * time.Minute)
	}

	ctx, cancel := context.WithTimeout(context.Background(), 5*time.Second)
	defer cancel()

	if err := sqlDB.PingContext(ctx); err != nil {
		return nil, err
	}

	return db, nil
}

func buildCache(cfg *config.Config) (middleware.CacheStore, func(), error) {
	if cfg.CacheDriver == "redis" {
		redisStore := cache.NewRedisStoreFromAddr(cfg.RedisAddr, cfg.RedisPassword, cfg.RedisDB)

		ctx, cancel := context.WithTimeout(context.Background(), 5*time.Second)
		defer cancel()

		if err := redisStore.Ping(ctx); err != nil {
			return nil, func() {}, err
		}

		slog.Info("cache Redis aktif", "addr", cfg.RedisAddr, "db", cfg.RedisDB)

		return redisStore, func() {
			_ = redisStore.Close()
		}, nil
	}

	slog.Warn("cache in-memory aktif; gunakan Redis untuk deployment multi-instance")

	return cache.NewMemoryStore(), func() {}, nil
}

func setupLogger(level string) {
	logLevel := slog.LevelInfo

	switch level {
	case "debug":
		logLevel = slog.LevelDebug
	case "warn", "warning":
		logLevel = slog.LevelWarn
	case "error":
		logLevel = slog.LevelError
	}

	handlerOptions := &slog.HandlerOptions{Level: logLevel}
	slog.SetDefault(slog.New(slog.NewJSONHandler(os.Stdout, handlerOptions)))
}
