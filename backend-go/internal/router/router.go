// Package router merakit Gin engine beserta kelompok rute API E-SPP.
package router

import (
	"time"

	"github.com/gin-gonic/gin"
	"gorm.io/gorm"

	"spp-backend/internal/middleware"
	"spp-backend/internal/response"
)

// Handlers adalah kumpulan handler yang disuntikkan oleh main atau test.
type Handlers struct {
	Health gin.HandlerFunc
	Ready  gin.HandlerFunc

	Login         gin.HandlerFunc
	PublicReceipt gin.HandlerFunc

	StudentDashboard gin.HandlerFunc
	StudentCard      gin.HandlerFunc
	StudentReceipt   gin.HandlerFunc

	WaliTunggakan gin.HandlerFunc
	WaliRekap     gin.HandlerFunc

	CreatePayment gin.HandlerFunc
	GetPayment    gin.HandlerFunc
	CreateExpense gin.HandlerFunc
	VoidPayment   gin.HandlerFunc

	AdminUsers   gin.HandlerFunc
	AdminTariffs gin.HandlerFunc

	ProReport gin.HandlerFunc
	ProExport gin.HandlerFunc
}

// Deps adalah dependensi yang dibutuhkan untuk merakit router.
type Deps struct {
	JWTSecret string
	Cache     middleware.CacheStore
	License   middleware.LicenseChecker
	DB        *gorm.DB
	Handlers  Handlers

	TrustedProxies []string

	RateLimitLogin   int
	RateLimitReceipt int
	RateLimitWindow  time.Duration
	IdempotencyTTL   time.Duration
}

// New membuat Gin engine lengkap dengan seluruh kelompok rute.
func New(deps Deps) *gin.Engine {
	if deps.JWTSecret == "" {
		panic("router.New: JWTSecret tidak boleh kosong")
	}

	rateLimitWindow := deps.RateLimitWindow
	if rateLimitWindow <= 0 {
		rateLimitWindow = time.Minute
	}

	rateLimitLogin := deps.RateLimitLogin
	if rateLimitLogin <= 0 {
		rateLimitLogin = 5
	}

	rateLimitReceipt := deps.RateLimitReceipt
	if rateLimitReceipt <= 0 {
		rateLimitReceipt = 30
	}

	idempotencyTTL := deps.IdempotencyTTL
	if idempotencyTTL <= 0 {
		idempotencyTTL = 30 * time.Second
	}

	proxies := deps.TrustedProxies
	if proxies == nil {
		proxies = []string{"127.0.0.1"}
	}

	engine := gin.New()
	engine.HandleMethodNotAllowed = true

	if err := engine.SetTrustedProxies(proxies); err != nil {
		panic("router.New: TRUSTED_PROXIES tidak valid: " + err.Error())
	}

	// JSONRecovery harus berada di lapisan terluar.
	engine.Use(middleware.JSONRecovery())

	if deps.DB != nil {
		engine.Use(middleware.AuditLogger(deps.DB))
	}

	handlers := deps.Handlers

	if handlers.Health != nil {
		engine.GET("/healthz", handlers.Health)
	}

	if handlers.Ready != nil {
		engine.GET("/readyz", handlers.Ready)
	}

	engine.NoRoute(func(c *gin.Context) {
		response.Abort(c, 404, response.CodeNotFound, "Rute API tidak ditemukan.")
	})
	engine.NoMethod(func(c *gin.Context) {
		response.Abort(c, 405, response.CodeMethodNotAllowed, "Metode HTTP tidak diizinkan untuk rute ini.")
	})

	authJWT := middleware.AuthJWT(deps.JWTSecret)
	role := middleware.RequireRole

	api := engine.Group("/api/v1")

	public := api.Group("/public")
	public.POST("/login", middleware.RateLimiter(rateLimitLogin, rateLimitWindow), handlerOr(handlers.Login))
	public.GET("/bukti/:kode/:token", middleware.RateLimiter(rateLimitReceipt, rateLimitWindow), handlerOr(handlers.PublicReceipt))

	siswa := api.Group("/siswa", authJWT, role(middleware.RoleSiswa))
	siswa.GET("/dashboard", handlerOr(handlers.StudentDashboard))
	siswa.GET("/kartu-spp", handlerOr(handlers.StudentCard))
	siswa.GET("/kuitansi/:kode", handlerOr(handlers.StudentReceipt))

	wali := api.Group("/wali", authJWT, role(middleware.RoleWaliKelas))
	wali.GET("/tunggakan", handlerOr(handlers.WaliTunggakan))
	wali.GET("/rekap-kelas/:kelas_id", handlerOr(handlers.WaliRekap))

	kasir := api.Group("/kasir", authJWT, role(middleware.RoleAdmin, middleware.RolePetugas))
	kasir.Use(middleware.IdempotencyLock(deps.Cache, idempotencyTTL))
	kasir.POST("/pembayaran", handlerOr(handlers.CreatePayment))
	kasir.GET("/pembayaran/:kode", handlerOr(handlers.GetPayment))
	kasir.POST("/pengeluaran", handlerOr(handlers.CreateExpense))

	admin := api.Group("/admin", authJWT, role(middleware.RoleAdmin))
	admin.GET("/users", handlerOr(handlers.AdminUsers))
	admin.POST("/tarif", handlerOr(handlers.AdminTariffs))
	admin.POST("/pembayaran/:kode/void", handlerOr(handlers.VoidPayment))

	pro := api.Group("/pro", authJWT, middleware.EnsureProTier(deps.License))
	pro.GET("/rekap-lanjutan", middleware.EnsureProTier(deps.License, "rekap_lanjutan"), handlerOr(handlers.ProReport))
	pro.POST("/export-gabungan", middleware.EnsureProTier(deps.License, "pembayaran_gabungan_excel"), handlerOr(handlers.ProExport))

	return engine
}

func handlerOr(handler gin.HandlerFunc) gin.HandlerFunc {
	if handler != nil {
		return handler
	}

	return notImplemented
}

func notImplemented(c *gin.Context) {
	response.Error(
		c,
		501,
		response.CodeNotImplemented,
		"Endpoint ini belum diimplementasikan pada skeleton backend Go.",
	)
}
