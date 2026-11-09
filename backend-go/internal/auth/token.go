// Package auth berisi klaim JWT dan helper penerbitan/verifikasi token.
package auth

import (
	"crypto/rand"
	"encoding/hex"
	"errors"
	"fmt"
	"strings"
	"time"

	"github.com/golang-jwt/jwt/v5"
)

// ErrInvalidToken dipakai untuk semua kegagalan verifikasi token agar
// pemanggil tidak membocorkan detail kriptografi ke pengguna.
var ErrInvalidToken = errors.New("token tidak valid")

// Claims adalah payload JWT E-SPP.
type Claims struct {
	UserID   uint   `json:"user_id"`
	Username string `json:"username"`
	Role     string `json:"role"`
	SiswaID  *uint  `json:"siswa_id,omitempty"`
	jwt.RegisteredClaims
}

// TokenManager menerbitkan token HS256 dengan masa berlaku tertentu.
type TokenManager struct {
	secret string
	ttl    time.Duration
	issuer string
	now    func() time.Time
}

// NewTokenManager membuat penerbit token. TTL <= 0 akan memakai 12 jam.
func NewTokenManager(secret string, ttl time.Duration) *TokenManager {
	if ttl <= 0 {
		ttl = 12 * time.Hour
	}

	return &TokenManager{
		secret: secret,
		ttl:    ttl,
		issuer: "spp-api",
		now:    time.Now,
	}
}

// Issue membuat token bertanda tangan untuk satu pengguna.
func (m *TokenManager) Issue(userID uint, username, role string, siswaID *uint) (string, time.Time, error) {
	if strings.TrimSpace(m.secret) == "" {
		return "", time.Time{}, errors.New("JWT secret belum dikonfigurasi")
	}

	now := m.now()
	expiresAt := now.Add(m.ttl)

	claims := Claims{
		UserID:   userID,
		Username: username,
		Role:     strings.ToLower(strings.TrimSpace(role)),
		SiswaID:  siswaID,
		RegisteredClaims: jwt.RegisteredClaims{
			Issuer:    m.issuer,
			Subject:   fmt.Sprintf("%d", userID),
			ID:        newTokenID(),
			IssuedAt:  jwt.NewNumericDate(now),
			NotBefore: jwt.NewNumericDate(now.Add(-5 * time.Second)),
			ExpiresAt: jwt.NewNumericDate(expiresAt),
		},
	}

	signed, err := jwt.NewWithClaims(jwt.SigningMethodHS256, claims).SignedString([]byte(m.secret))
	if err != nil {
		return "", time.Time{}, fmt.Errorf("gagal menandatangani token: %w", err)
	}

	return signed, expiresAt, nil
}

// ParseToken memverifikasi tanda tangan, algoritma, dan masa berlaku token.
// Semua kegagalan dipetakan ke ErrInvalidToken.
func ParseToken(secret, raw string) (*Claims, error) {
	if strings.TrimSpace(secret) == "" || strings.TrimSpace(raw) == "" {
		return nil, ErrInvalidToken
	}

	claims := &Claims{}

	token, err := jwt.ParseWithClaims(
		raw,
		claims,
		func(token *jwt.Token) (any, error) {
			if _, ok := token.Method.(*jwt.SigningMethodHMAC); !ok {
				return nil, fmt.Errorf("%w: algoritma tanda tangan tidak didukung", ErrInvalidToken)
			}

			return []byte(secret), nil
		},
		jwt.WithValidMethods([]string{jwt.SigningMethodHS256.Alg()}),
		jwt.WithExpirationRequired(),
		jwt.WithLeeway(30*time.Second),
	)
	if err != nil {
		return nil, fmt.Errorf("%w: %v", ErrInvalidToken, err)
	}

	if !token.Valid {
		return nil, ErrInvalidToken
	}

	if claims.UserID == 0 ||
		strings.TrimSpace(claims.Username) == "" ||
		strings.TrimSpace(claims.Role) == "" {
		return nil, fmt.Errorf("%w: klaim wajib tidak lengkap", ErrInvalidToken)
	}

	claims.Role = strings.ToLower(strings.TrimSpace(claims.Role))

	return claims, nil
}

func newTokenID() string {
	buf := make([]byte, 16)
	if _, err := rand.Read(buf); err != nil {
		return fmt.Sprintf("%d", time.Now().UnixNano())
	}

	return hex.EncodeToString(buf)
}
