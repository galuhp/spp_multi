package middleware

import (
	"strings"

	"github.com/gin-gonic/gin"

	"spp-backend/internal/auth"
	"spp-backend/internal/response"
)

// AuthJWT memvalidasi header Authorization: Bearer <token> lalu menyimpan
// seluruh klaim ke context request.
func AuthJWT(jwtSecret string) gin.HandlerFunc {
	if strings.TrimSpace(jwtSecret) == "" {
		panic("middleware.AuthJWT: jwtSecret tidak boleh kosong")
	}

	return func(c *gin.Context) {
		rawToken, ok := bearerToken(c.GetHeader("Authorization"))
		if !ok {
			abortUnauthenticated(c)

			return
		}

		claims, err := auth.ParseToken(jwtSecret, rawToken)
		if err != nil {
			abortUnauthenticated(c)

			return
		}

		c.Set(ContextUserID, claims.UserID)
		c.Set(ContextUsername, claims.Username)
		c.Set(ContextRole, claims.Role)
		c.Set(ContextSiswaID, claims.SiswaID)
		c.Set(ContextClaims, claims)

		c.Next()
	}
}

func bearerToken(header string) (string, bool) {
	parts := strings.Fields(strings.TrimSpace(header))
	if len(parts) != 2 || !strings.EqualFold(parts[0], "Bearer") {
		return "", false
	}

	token := strings.TrimSpace(parts[1])
	if token == "" {
		return "", false
	}

	return token, true
}

func abortUnauthenticated(c *gin.Context) {
	response.Abort(
		c,
		401,
		response.CodeUnauthenticated,
		"Sesi tidak valid atau telah berakhir. Silakan masuk kembali.",
	)
}
