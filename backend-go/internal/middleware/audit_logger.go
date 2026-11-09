package middleware

import (
	"context"
	"log/slog"
	"net/http"
	"sync"
	"time"
	"unicode/utf8"

	"github.com/gin-gonic/gin"
	"gorm.io/gorm"

	"spp-backend/internal/model"
)

const (
	auditQueueSize  = 1024
	auditWorkers    = 2
	auditWriteLimit = 5 * time.Second
)

// AuditLogger mencatat aksi mutasi (POST/PUT/PATCH/DELETE) secara asinkron.
// Penulisan dilakukan oleh worker terbatas agar lonjakan trafik kasir tidak
// membuat goroutine tak terkendali.
func AuditLogger(db *gorm.DB) gin.HandlerFunc {
	if db == nil {
		panic("middleware.AuditLogger: db tidak boleh nil")
	}

	logger := &auditLogger{
		db:    db,
		queue: make(chan model.AuditLog, auditQueueSize),
	}

	return logger.handle
}

type auditLogger struct {
	db    *gorm.DB
	queue chan model.AuditLog
	once  sync.Once
}

func (a *auditLogger) handle(c *gin.Context) {
	if !isMutatingMethod(c.Request.Method) {
		c.Next()

		return
	}

	a.once.Do(func() {
		for i := 0; i < auditWorkers; i++ {
			go a.worker()
		}
	})

	startedAt := time.Now()

	defer func() {
		recovered := recover()
		entry := a.entry(c, startedAt)

		if recovered != nil {
			entry.StatusCode = http.StatusInternalServerError
			a.enqueue(entry)
			// Teruskan panic ke JSONRecovery agar respons 500 tetap dikirim.
			panic(recovered)
		}

		a.enqueue(entry)
	}()

	c.Next()
}

func (a *auditLogger) entry(c *gin.Context, startedAt time.Time) model.AuditLog {
	var userID *uint
	username := ""

	if user, ok := CurrentUser(c); ok {
		id := user.UserID
		userID = &id
		username = truncateUTF8(user.Username, 100)
	}

	return model.AuditLog{
		UserID:     userID,
		Username:   username,
		Method:     truncateUTF8(c.Request.Method, 10),
		Path:       truncateUTF8(c.Request.URL.Path, 255),
		IPAddress:  truncateUTF8(c.ClientIP(), 45),
		UserAgent:  truncateUTF8(c.Request.UserAgent(), 512),
		LatencyMs:  time.Since(startedAt).Milliseconds(),
		StatusCode: c.Writer.Status(),
	}
}

func (a *auditLogger) enqueue(entry model.AuditLog) {
	select {
	case a.queue <- entry:
	default:
		slog.Warn(
			"antrean audit log penuh, entri dibuang",
			"method", entry.Method,
			"path", entry.Path,
			"status", entry.StatusCode,
		)
	}
}

func (a *auditLogger) worker() {
	for entry := range a.queue {
		ctx, cancel := context.WithTimeout(context.Background(), auditWriteLimit)
		err := a.db.WithContext(ctx).Create(&entry).Error
		cancel()

		if err != nil {
			slog.Error(
				"gagal menyimpan audit log",
				"error", err,
				"method", entry.Method,
				"path", entry.Path,
			)
		}
	}
}

func truncateUTF8(value string, maxBytes int) string {
	if len(value) <= maxBytes {
		return value
	}

	limit := maxBytes
	for limit > 0 && !utf8.ValidString(value[:limit]) {
		limit--
	}

	return value[:limit]
}
