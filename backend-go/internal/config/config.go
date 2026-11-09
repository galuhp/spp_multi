// Package config memuat konfigurasi backend dari environment variable.
package config

import (
	"encoding/json"
	"errors"
	"fmt"
	"os"
	"strconv"
	"strings"
	"time"

	"spp-backend/internal/license"
)

// DefaultFeatures memetakan fitur ke tier minimum, mengikuti config/spp.php
// pada aplikasi Laravel.
var DefaultFeatures = map[string]string{
	// Fitur Basic.
	"pembayaran_kasir":       "basic",
	"pembayaran_kolektif":    "basic",
	"tanggungan_pemerintah":  "basic",
	"laporan_standar":        "basic",
	"kenaikan_dan_kelulusan": "basic",

	// Fitur Pro.
	"pembayaran_gabungan_excel": "pro",
	"whatsapp_gateway":          "pro",
	"rekap_lanjutan":            "pro",
}

// Config adalah konfigurasi runtime aplikasi.
type Config struct {
	AppEnv   string
	GinMode  string
	Port     string
	LogLevel string

	JWTSecret string
	JWTTTL    time.Duration

	DBDSN       string
	AutoMigrate bool

	CacheDriver   string
	RedisAddr     string
	RedisPassword string
	RedisDB       int

	AppTier  string
	Features map[string]string

	TrustedProxies []string

	RateLimitLogin   int
	RateLimitReceipt int
	IdempotencyTTL   time.Duration

	ReadHeaderTimeout time.Duration
	ReadTimeout       time.Duration
	WriteTimeout      time.Duration
	IdleTimeout       time.Duration
	ShutdownTimeout   time.Duration
}

// Load membaca dan memvalidasi environment variable.
func Load() (*Config, error) {
	var problems []error

	jwtTTL, err := envDuration("JWT_TTL", 12*time.Hour)
	if err != nil {
		problems = append(problems, err)
	}

	idempotencyTTL, err := envDuration("IDEMPOTENCY_TTL", 30*time.Second)
	if err != nil {
		problems = append(problems, err)
	}

	readHeaderTimeout, err := envDuration("SERVER_READ_HEADER_TIMEOUT", 10*time.Second)
	if err != nil {
		problems = append(problems, err)
	}

	readTimeout, err := envDuration("SERVER_READ_TIMEOUT", 30*time.Second)
	if err != nil {
		problems = append(problems, err)
	}

	writeTimeout, err := envDuration("SERVER_WRITE_TIMEOUT", 60*time.Second)
	if err != nil {
		problems = append(problems, err)
	}

	idleTimeout, err := envDuration("SERVER_IDLE_TIMEOUT", 120*time.Second)
	if err != nil {
		problems = append(problems, err)
	}

	shutdownTimeout, err := envDuration("SHUTDOWN_TIMEOUT", 20*time.Second)
	if err != nil {
		problems = append(problems, err)
	}

	redisDB, err := envInt("REDIS_DB", 0)
	if err != nil {
		problems = append(problems, err)
	}

	rateLimitLogin, err := envInt("RATE_LIMIT_LOGIN", 5)
	if err != nil {
		problems = append(problems, err)
	}

	rateLimitReceipt, err := envInt("RATE_LIMIT_RECEIPT", 30)
	if err != nil {
		problems = append(problems, err)
	}

	autoMigrate, err := envBool("DB_AUTO_MIGRATE", false)
	if err != nil {
		problems = append(problems, err)
	}

	features, err := loadFeatures()
	if err != nil {
		problems = append(problems, err)
	}

	cfg := &Config{
		AppEnv:            env("APP_ENV", "production"),
		GinMode:           env("GIN_MODE", "release"),
		Port:              env("APP_PORT", "8080"),
		LogLevel:          strings.ToLower(env("LOG_LEVEL", "info")),
		JWTSecret:         strings.TrimSpace(os.Getenv("JWT_SECRET")),
		JWTTTL:            jwtTTL,
		DBDSN:             strings.TrimSpace(os.Getenv("DB_DSN")),
		AutoMigrate:       autoMigrate,
		CacheDriver:       strings.ToLower(env("CACHE_DRIVER", "memory")),
		RedisAddr:         env("REDIS_ADDR", "127.0.0.1:6379"),
		RedisPassword:     os.Getenv("REDIS_PASSWORD"),
		RedisDB:           redisDB,
		AppTier:           strings.ToLower(env("APP_TIER", "basic")),
		Features:          features,
		TrustedProxies:    parseTrustedProxies(env("TRUSTED_PROXIES", "127.0.0.1")),
		RateLimitLogin:    rateLimitLogin,
		RateLimitReceipt:  rateLimitReceipt,
		IdempotencyTTL:    idempotencyTTL,
		ReadHeaderTimeout: readHeaderTimeout,
		ReadTimeout:       readTimeout,
		WriteTimeout:      writeTimeout,
		IdleTimeout:       idleTimeout,
		ShutdownTimeout:   shutdownTimeout,
	}

	if len(cfg.JWTSecret) < 32 {
		problems = append(problems, errors.New("JWT_SECRET wajib diisi minimal 32 karakter"))
	}

	if cfg.DBDSN == "" {
		problems = append(problems, errors.New("DB_DSN wajib diisi"))
	}

	if cfg.CacheDriver != "memory" && cfg.CacheDriver != "redis" {
		problems = append(problems, fmt.Errorf("CACHE_DRIVER tidak dikenal: %s (gunakan memory atau redis)", cfg.CacheDriver))
	}

	if license.NormalizeTier(cfg.AppTier) == license.TierBasic && cfg.AppTier != string(license.TierBasic) {
		cfg.AppTier = string(license.TierBasic)
	}

	if len(problems) > 0 {
		return nil, errors.Join(problems...)
	}

	return cfg, nil
}

func loadFeatures() (map[string]string, error) {
	features := make(map[string]string, len(DefaultFeatures))
	for key, value := range DefaultFeatures {
		features[key] = value
	}

	raw := strings.TrimSpace(os.Getenv("APP_FEATURES_JSON"))
	if raw == "" {
		return features, nil
	}

	var overrides map[string]string
	if err := json.Unmarshal([]byte(raw), &overrides); err != nil {
		return nil, fmt.Errorf("APP_FEATURES_JSON tidak valid: %w", err)
	}

	for key, value := range overrides {
		features[key] = value
	}

	return features, nil
}

func parseTrustedProxies(raw string) []string {
	if strings.EqualFold(strings.TrimSpace(raw), "none") || strings.TrimSpace(raw) == "" {
		return []string{}
	}

	parts := strings.Split(raw, ",")
	proxies := make([]string, 0, len(parts))
	for _, part := range parts {
		if proxy := strings.TrimSpace(part); proxy != "" {
			proxies = append(proxies, proxy)
		}
	}

	return proxies
}

func env(key, fallback string) string {
	value := strings.TrimSpace(os.Getenv(key))
	if value == "" {
		return fallback
	}

	return value
}

func envInt(key string, fallback int) (int, error) {
	raw := strings.TrimSpace(os.Getenv(key))
	if raw == "" {
		return fallback, nil
	}

	value, err := strconv.Atoi(raw)
	if err != nil {
		return fallback, fmt.Errorf("%s harus berupa angka: %w", key, err)
	}

	return value, nil
}

func envDuration(key string, fallback time.Duration) (time.Duration, error) {
	raw := strings.TrimSpace(os.Getenv(key))
	if raw == "" {
		return fallback, nil
	}

	value, err := time.ParseDuration(raw)
	if err != nil {
		return fallback, fmt.Errorf("%s harus berupa durasi (contoh 30s, 12h): %w", key, err)
	}

	return value, nil
}

func envBool(key string, fallback bool) (bool, error) {
	raw := strings.TrimSpace(os.Getenv(key))
	if raw == "" {
		return fallback, nil
	}

	value, err := strconv.ParseBool(raw)
	if err != nil {
		return fallback, fmt.Errorf("%s harus berupa boolean: %w", key, err)
	}

	return value, nil
}
