// Package response menyediakan format amplop JSON yang seragam untuk seluruh
// endpoint dan middleware E-SPP.
package response

import "github.com/gin-gonic/gin"

// Kode error yang dipakai lintas middleware dan handler.
const (
	CodeUnauthenticated        = "UNAUTHENTICATED"
	CodeForbidden              = "FORBIDDEN"
	CodeTierUpgradeRequired    = "TIER_UPGRADE_REQUIRED"
	CodeLicenseCheckFailed     = "LICENSE_CHECK_FAILED"
	CodeIdempotencyConflict    = "IDEMPOTENCY_CONFLICT"
	CodeIdempotencyKeyMissing  = "IDEMPOTENCY_KEY_REQUIRED"
	CodeIdempotencyKeyInvalid  = "INVALID_IDEMPOTENCY_KEY"
	CodeIdempotencyUnavailable = "IDEMPOTENCY_UNAVAILABLE"
	CodeTooManyRequests        = "TOO_MANY_REQUESTS"
	CodeInternalServerError    = "INTERNAL_SERVER_ERROR"
	CodeServiceUnavailable     = "SERVICE_UNAVAILABLE"
	CodeNotImplemented         = "NOT_IMPLEMENTED"
	CodeNotFound               = "NOT_FOUND"
	CodeMethodNotAllowed       = "METHOD_NOT_ALLOWED"
)

// Envelope adalah bentuk respons JSON standar aplikasi.
//
//	{
//	  "success": false,
//	  "message": "Pesan error bahasa Indonesia yang ramah pengguna",
//	  "code": "OPTIONAL_ERROR_CODE",
//	  "data": null
//	}
type Envelope struct {
	Success bool   `json:"success"`
	Message string `json:"message"`
	Code    string `json:"code,omitempty"`
	Data    any    `json:"data"`
}

// Success menulis respons sukses dengan status HTTP tertentu.
func Success(c *gin.Context, status int, message string, data any) {
	c.JSON(status, Envelope{
		Success: true,
		Message: message,
		Data:    data,
	})
}

// Error menulis respons gagal tanpa menghentikan rantai middleware.
func Error(c *gin.Context, status int, code, message string) {
	c.JSON(status, Envelope{
		Success: false,
		Message: message,
		Code:    code,
		Data:    nil,
	})
}

// Abort menulis respons gagal sekaligus menghentikan rantai middleware.
func Abort(c *gin.Context, status int, code, message string) {
	c.AbortWithStatusJSON(status, Envelope{
		Success: false,
		Message: message,
		Code:    code,
		Data:    nil,
	})
}
