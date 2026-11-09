// Package model berisi model GORM yang dipakai backend Go E-SPP.
package model

import "time"

// AuditLog menyimpan jejak aksi mutasi yang sensitif.
// Tabel dipisahkan dari `log_aktivitas` milik aplikasi Laravel agar skema
// keduanya dapat berevolusi tanpa saling mengunci.
type AuditLog struct {
	ID         uint      `gorm:"primaryKey" json:"id"`
	UserID     *uint     `gorm:"index" json:"user_id"`
	Username   string    `gorm:"size:100" json:"username"`
	Method     string    `gorm:"size:10;not null" json:"method"`
	Path       string    `gorm:"size:255;not null;index" json:"path"`
	IPAddress  string    `gorm:"size:45" json:"ip_address"`
	UserAgent  string    `gorm:"size:512" json:"user_agent"`
	LatencyMs  int64     `json:"latency_ms"`
	StatusCode int       `gorm:"index" json:"status_code"`
	CreatedAt  time.Time `json:"created_at"`
}

// TableName menentukan nama tabel audit.
func (AuditLog) TableName() string {
	return "audit_logs"
}
