package middleware

import (
	"context"
	"time"

	"github.com/gin-gonic/gin"

	"spp-backend/internal/auth"
)

// Role yang dikenal oleh sistem E-SPP.
const (
	RoleAdmin     = "admin"
	RolePetugas   = "petugas"
	RoleWaliKelas = "wali_kelas"
	RoleSiswa     = "siswa"
)

// Key context yang dipakai setelah AuthJWT berhasil.
const (
	ContextUserID   = "user_id"
	ContextUsername = "username"
	ContextRole     = "role"
	ContextSiswaID  = "siswa_id"
	ContextClaims   = "claims"
)

// LicenseChecker adalah abstraksi sumber kebenaran lisensi paket aplikasi.
// Implementasi bawaan ada di package internal/license, tetapi aplikasi dapat
// menggantinya dengan pembacaan database, API lisensi, atau cache.
type LicenseChecker interface {
	Tier(ctx context.Context) (string, error)
	HasFeature(ctx context.Context, feature string) (bool, error)
}

// CacheStore adalah subset operasi cache yang dibutuhkan IdempotencyLock.
// Implementasi bawaan tersedia untuk Redis dan in-memory.
type CacheStore interface {
	SetNX(ctx context.Context, key, value string, ttl time.Duration) (bool, error)
	Delete(ctx context.Context, key string) error
}

// AuthUser adalah identitas pengguna yang tersimpan di context.
type AuthUser struct {
	UserID   uint
	Username string
	Role     string
	SiswaID  *uint
}

// CurrentUser mengambil identitas pengguna yang dipasang oleh AuthJWT.
func CurrentUser(c *gin.Context) (AuthUser, bool) {
	rawUserID, exists := c.Get(ContextUserID)
	if !exists {
		return AuthUser{}, false
	}

	userID, ok := rawUserID.(uint)
	if !ok || userID == 0 {
		return AuthUser{}, false
	}

	username, _ := c.Get(ContextUsername)
	role, _ := c.Get(ContextRole)
	siswaID, _ := c.Get(ContextSiswaID)

	user := AuthUser{
		UserID:   userID,
		Username: stringValue(username),
		Role:     stringValue(role),
	}

	if id, ok := siswaID.(*uint); ok {
		user.SiswaID = id
	}

	return user, true
}

// CurrentClaims mengambil seluruh klaim JWT yang sudah divalidasi.
func CurrentClaims(c *gin.Context) (*auth.Claims, bool) {
	raw, exists := c.Get(ContextClaims)
	if !exists {
		return nil, false
	}

	claims, ok := raw.(*auth.Claims)

	return claims, ok
}

func stringValue(value any) string {
	if str, ok := value.(string); ok {
		return str
	}

	return ""
}
