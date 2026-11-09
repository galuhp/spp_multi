package middleware_test

import (
	"fmt"
	"testing"
	"time"

	"github.com/gin-gonic/gin"
	"github.com/glebarez/sqlite"
	"gorm.io/gorm"

	"spp-backend/internal/middleware"
	"spp-backend/internal/model"
)

func TestAuditLoggerRecordsMutatingRequest(t *testing.T) {
	db := newAuditDatabase(t)

	engine := gin.New()
	engine.Use(middleware.JSONRecovery(), middleware.AuditLogger(db))
	engine.Use(func(c *gin.Context) {
		c.Set(middleware.ContextUserID, uint(42))
		c.Set(middleware.ContextUsername, "petugas.satu")
		c.Next()
	})
	engine.POST("/api/v1/kasir/pembayaran", func(c *gin.Context) {
		c.Status(201)
	})
	engine.GET("/api/v1/kasir/pembayaran/1", func(c *gin.Context) {
		c.Status(200)
	})

	request := newRequest("POST", "/api/v1/kasir/pembayaran?metode=tunai")
	request.Header.Set("User-Agent", "Kasir-Test/1.0")
	request.RemoteAddr = "203.0.113.77:9999"

	recorder := performRequest(engine, request)
	if recorder.Code != 201 {
		t.Fatalf("status = %d, ingin 201", recorder.Code)
	}

	entry := waitForAuditLog(t, db, 1)[0]

	if entry.UserID == nil || *entry.UserID != 42 {
		t.Fatalf("user_id = %v, ingin 42", entry.UserID)
	}

	if entry.Username != "petugas.satu" {
		t.Fatalf("username = %q", entry.Username)
	}

	if entry.Method != "POST" || entry.Path != "/api/v1/kasir/pembayaran" {
		t.Fatalf("method/path = %q %q", entry.Method, entry.Path)
	}

	if entry.IPAddress != "203.0.113.77" {
		t.Fatalf("ip = %q, ingin 203.0.113.77", entry.IPAddress)
	}

	if entry.UserAgent != "Kasir-Test/1.0" {
		t.Fatalf("user agent = %q", entry.UserAgent)
	}

	if entry.StatusCode != 201 {
		t.Fatalf("status code = %d, ingin 201", entry.StatusCode)
	}

	if entry.LatencyMs < 0 {
		t.Fatalf("latency tidak boleh negatif")
	}

	if entry.CreatedAt.IsZero() {
		t.Fatalf("created_at kosong")
	}

	getRequest := newRequest("GET", "/api/v1/kasir/pembayaran/1")
	getRequest.RemoteAddr = "203.0.113.77:9999"
	if recorder := performRequest(engine, getRequest); recorder.Code != 200 {
		t.Fatalf("GET status = %d, ingin 200", recorder.Code)
	}

	time.Sleep(100 * time.Millisecond)

	var count int64
	if err := db.Model(&model.AuditLog{}).Count(&count).Error; err != nil {
		t.Fatalf("gagal menghitung audit log: %v", err)
	}

	if count != 1 {
		t.Fatalf("jumlah audit log = %d, ingin 1 (GET tidak boleh diaudit)", count)
	}
}

func TestAuditLoggerRecordsPanicAs500(t *testing.T) {
	db := newAuditDatabase(t)

	engine := gin.New()
	engine.Use(middleware.JSONRecovery(), middleware.AuditLogger(db))
	engine.POST("/api/v1/kasir/pembayaran", func(c *gin.Context) {
		panic("gagal menyimpan pembayaran")
	})

	recorder := performRequest(engine, newRequest("POST", "/api/v1/kasir/pembayaran"))
	assertEnvelopeError(t, recorder, 500, "INTERNAL_SERVER_ERROR")

	entry := waitForAuditLog(t, db, 1)[0]

	if entry.StatusCode != 500 {
		t.Fatalf("status code audit = %d, ingin 500", entry.StatusCode)
	}

	if entry.UserID != nil {
		t.Fatalf("user_id = %v, ingin nil untuk request tanpa auth", entry.UserID)
	}
}

func newAuditDatabase(t *testing.T) *gorm.DB {
	t.Helper()

	dsn := fmt.Sprintf("file:audit_%d?mode=memory&cache=shared", time.Now().UnixNano())

	db, err := gorm.Open(sqlite.Open(dsn), &gorm.Config{})
	if err != nil {
		t.Fatalf("gagal membuka database uji: %v", err)
	}

	sqlDB, err := db.DB()
	if err != nil {
		t.Fatalf("gagal membaca koneksi database uji: %v", err)
	}

	sqlDB.SetMaxOpenConns(1)
	t.Cleanup(func() {
		_ = sqlDB.Close()
	})

	if err := db.AutoMigrate(&model.AuditLog{}); err != nil {
		t.Fatalf("gagal migrasi tabel audit: %v", err)
	}

	return db
}

func waitForAuditLog(t *testing.T, db *gorm.DB, wanted int) []model.AuditLog {
	t.Helper()

	deadline := time.Now().Add(3 * time.Second)
	var entries []model.AuditLog

	for time.Now().Before(deadline) {
		if err := db.Order("id asc").Find(&entries).Error; err != nil {
			t.Fatalf("gagal membaca audit log: %v", err)
		}

		if len(entries) >= wanted {
			return entries
		}

		time.Sleep(10 * time.Millisecond)
	}

	t.Fatalf("audit log tidak muncul dalam batas waktu; ditemukan %d, ingin %d", len(entries), wanted)

	return nil
}
