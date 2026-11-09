package middleware

import (
	"strings"

	"github.com/gin-gonic/gin"

	"spp-backend/internal/response"
)

// RequireRole memastikan pengguna yang sudah terautentikasi memiliki salah
// satu role yang diizinkan.
func RequireRole(allowedRoles ...string) gin.HandlerFunc {
	allowed := make(map[string]struct{}, len(allowedRoles))
	for _, role := range allowedRoles {
		normalized := strings.ToLower(strings.TrimSpace(role))
		if normalized != "" {
			allowed[normalized] = struct{}{}
		}
	}

	return func(c *gin.Context) {
		rawRole, exists := c.Get(ContextRole)
		if !exists {
			abortUnauthenticated(c)

			return
		}

		role, ok := rawRole.(string)
		if !ok || strings.TrimSpace(role) == "" {
			abortUnauthenticated(c)

			return
		}

		if _, permitted := allowed[strings.ToLower(strings.TrimSpace(role))]; permitted {
			c.Next()

			return
		}

		response.Abort(
			c,
			403,
			response.CodeForbidden,
			"Anda tidak memiliki hak akses untuk rute ini.",
		)
	}
}
