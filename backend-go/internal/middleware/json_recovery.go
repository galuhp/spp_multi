package middleware

import (
	"errors"
	"log/slog"
	"net/http"
	"runtime/debug"

	"github.com/gin-gonic/gin"

	"spp-backend/internal/response"
)

// JSONRecovery menangkap panic dan mengubahnya menjadi respons JSON 500 yang
// konsisten tanpa menghentikan proses server.
func JSONRecovery() gin.HandlerFunc {
	return func(c *gin.Context) {
		defer func() {
			recovered := recover()
			if recovered == nil {
				return
			}

			if err, ok := recovered.(error); ok && errors.Is(err, http.ErrAbortHandler) {
				c.Abort()

				return
			}

			slog.Error(
				"panic tertangkap",
				"error", recovered,
				"method", c.Request.Method,
				"path", c.Request.URL.Path,
				"ip", c.ClientIP(),
				"stack", string(debug.Stack()),
			)

			c.Abort()

			// Header sudah terkirim: body JSON tidak dapat ditambahkan lagi.
			if c.Writer.Written() {
				return
			}

			response.Error(
				c,
				http.StatusInternalServerError,
				response.CodeInternalServerError,
				"Terjadi kesalahan internal pada server.",
			)
		}()

		c.Next()
	}
}
