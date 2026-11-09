package middleware_test

import (
	"testing"

	"github.com/gin-gonic/gin"

	"spp-backend/internal/middleware"
)

func TestRequireRoleAllowsMatchingRole(t *testing.T) {
	engine := gin.New()
	engine.Use(withRole(middleware.RoleAdmin), middleware.RequireRole(middleware.RoleAdmin))
	engine.GET("/admin", func(c *gin.Context) {
		c.Status(200)
	})

	recorder := performRequest(engine, newRequest("GET", "/admin"))

	if recorder.Code != 200 {
		t.Fatalf("status = %d, ingin 200 (body=%s)", recorder.Code, recorder.Body.String())
	}
}

func TestRequireRoleAllowsAnyListedRole(t *testing.T) {
	for _, role := range []string{middleware.RoleAdmin, middleware.RolePetugas} {
		t.Run(role, func(t *testing.T) {
			engine := gin.New()
			engine.Use(withRole(role), middleware.RequireRole(middleware.RoleAdmin, middleware.RolePetugas))
			engine.GET("/kasir", func(c *gin.Context) {
				c.Status(200)
			})

			recorder := performRequest(engine, newRequest("GET", "/kasir"))

			if recorder.Code != 200 {
				t.Fatalf("role %s: status = %d, ingin 200", role, recorder.Code)
			}
		})
	}
}

func TestRequireRoleRejectsWrongRole(t *testing.T) {
	engine := gin.New()
	engine.Use(withRole(middleware.RoleSiswa), middleware.RequireRole(middleware.RoleAdmin))
	engine.GET("/admin", func(c *gin.Context) {
		c.Status(200)
	})

	recorder := performRequest(engine, newRequest("GET", "/admin"))
	body := assertEnvelopeError(t, recorder, 403, "FORBIDDEN")

	if body.Message != "Anda tidak memiliki hak akses untuk rute ini." {
		t.Fatalf("message = %q", body.Message)
	}
}

func TestRequireRoleWithoutAuthContextReturnsUnauthorized(t *testing.T) {
	engine := gin.New()
	engine.Use(middleware.RequireRole(middleware.RoleAdmin))
	engine.GET("/admin", func(c *gin.Context) {
		c.Status(200)
	})

	recorder := performRequest(engine, newRequest("GET", "/admin"))
	assertEnvelopeError(t, recorder, 401, "UNAUTHENTICATED")
}

func TestRequireRoleDeniesWhenNoRoleIsAllowed(t *testing.T) {
	engine := gin.New()
	engine.Use(withRole(middleware.RoleAdmin), middleware.RequireRole())
	engine.GET("/admin", func(c *gin.Context) {
		c.Status(200)
	})

	recorder := performRequest(engine, newRequest("GET", "/admin"))
	assertEnvelopeError(t, recorder, 403, "FORBIDDEN")
}
