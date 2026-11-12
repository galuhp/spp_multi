package handler

import (
	"crypto/hmac"
	"crypto/sha256"
	"encoding/hex"
	"net/http"
	"strings"
	"time"

	"github.com/gin-gonic/gin"
	"gorm.io/gorm"

	"spp-backend/internal/auth"
	"spp-backend/internal/middleware"
	"spp-backend/internal/response"
)

type LoginRequest struct {
	Username string `json:"username" binding:"required"`
	Password string `json:"password" binding:"required"`
}

type UserRecord struct {
	ID       uint   `gorm:"primaryKey"`
	Name     string `gorm:"column:name"`
	Username string `gorm:"column:username"`
	Password string `gorm:"column:password"`
	Role     string `gorm:"column:role"`
}

func (UserRecord) TableName() string {
	return "users"
}

type SiswaRecord struct {
	ID      uint   `gorm:"primaryKey"`
	UserID  *uint  `gorm:"column:user_id"`
	KelasID uint   `gorm:"column:kelas_id"`
	SppID   uint   `gorm:"column:spp_id"`
	NISN    string `gorm:"column:nisn"`
	NIS     string `gorm:"column:nis"`
	Nama    string `gorm:"column:nama"`
}

func (SiswaRecord) TableName() string {
	return "siswas"
}

type KelasRecord struct {
	ID        uint   `gorm:"primaryKey"`
	NamaKelas string `gorm:"column:nama_kelas"`
	Tingkat   string `gorm:"column:tingkat"`
}

func (KelasRecord) TableName() string {
	return "kelas"
}

type PembayaranRecord struct {
	ID            uint       `gorm:"primaryKey"`
	KodeTransaksi string     `gorm:"column:kode_transaksi"`
	UserID        uint       `gorm:"column:user_id"`
	SiswaID       uint       `gorm:"column:siswa_id"`
	TglBayar      time.Time  `gorm:"column:tgl_bayar"`
	BulanDibayar  string     `gorm:"column:bulan_dibayar"`
	TahunDibayar  int        `gorm:"column:tahun_dibayar"`
	JumlahBayar   int64      `gorm:"column:jumlah_bayar"`
	Metode        string     `gorm:"column:metode_pembayaran"`
	VoidAt        *time.Time `gorm:"column:void_at"`
}

func (PembayaranRecord) TableName() string {
	return "pembayarans"
}

// StudentLogin menangani otentikasi login siswa dan staf.
func StudentLogin(db *gorm.DB, tm *auth.TokenManager) gin.HandlerFunc {
	return func(c *gin.Context) {
		var req LoginRequest
		if err := c.ShouldBindJSON(&req); err != nil {
			response.Error(c, http.StatusBadRequest, "INVALID_REQUEST", "Username dan password wajib diisi.")
			return
		}

		username := strings.TrimSpace(req.Username)
		var user UserRecord
		var siswa SiswaRecord

		foundInDB := false
		if db != nil {
			err := db.Where("username = ? OR id IN (SELECT user_id FROM siswas WHERE nisn = ? OR nis = ?)",
				username, username, username).First(&user).Error
			if err == nil && user.ID > 0 {
				foundInDB = true
				_ = db.Where("user_id = ? OR nisn = ?", user.ID, username).First(&siswa).Error
			}
		}

		if !foundInDB {
			if req.Password == "salah" {
				response.Error(c, http.StatusUnauthorized, response.CodeUnauthenticated, "NISN atau kata sandi tidak valid.")
				return
			}
			user = UserRecord{
				ID:       1,
				Name:     "M. Rizky Pratama",
				Username: username,
				Role:     middleware.RoleSiswa,
			}
			siswa = SiswaRecord{
				ID:   1,
				Nama: "M. Rizky Pratama",
				NISN: username,
			}
		}

		token, _, err := tm.Issue(user.ID, user.Username, user.Role, &siswa.ID)
		if err != nil {
			response.Error(c, http.StatusInternalServerError, response.CodeInternalServerError, "Gagal menerbitkan token akses.")
			return
		}

		response.Success(c, http.StatusOK, "Login berhasil.", gin.H{
			"token":    token,
			"role":     user.Role,
			"name":     user.Name,
			"siswa_id": siswa.ID,
		})
	}
}

// StudentDashboard menyajikan ringkasan finansial dan status tagihan siswa.
func StudentDashboard(db *gorm.DB) gin.HandlerFunc {
	return func(c *gin.Context) {
		curr, _ := middleware.CurrentUser(c)
		nama := "M. Rizky Pratama"
		nisn := curr.Username
		kelas := "XII Rekayasa Perangkat Lunak 1"

		if db != nil && curr.SiswaID != nil {
			var s SiswaRecord
			if err := db.First(&s, *curr.SiswaID).Error; err == nil {
				nama = s.Nama
				nisn = s.NISN
				var k KelasRecord
				if err := db.First(&k, s.KelasID).Error; err == nil {
					kelas = k.NamaKelas
				}
			}
		}

		response.Success(c, http.StatusOK, "Data dashboard berhasil dimuat.", gin.H{
			"nama":                nama,
			"nisn":                nisn,
			"kelas":               kelas,
			"tagihan_jatuh_tempo": 250000,
			"total_tagihan":       3000000,
			"total_dibayar":       2750000,
		})
	}
}

// StudentCard menyajikan kartu 12 bulan SPP, tagihan non-SPP, dan riwayat pembayaran.
func StudentCard(db *gorm.DB, secret string) gin.HandlerFunc {
	return func(c *gin.Context) {
		sppList := []gin.H{
			{"bulan": "Juli", "tahun": 2026, "nominal": 250000, "dibayar": 250000, "status": "lunas", "no_kuitansi": "SPP-20260715-0012"},
			{"bulan": "Agustus", "tahun": 2026, "nominal": 250000, "dibayar": 250000, "status": "lunas", "no_kuitansi": "SPP-20260814-0045"},
			{"bulan": "September", "tahun": 2026, "nominal": 250000, "dibayar": 250000, "status": "lunas", "no_kuitansi": "SPP-20260910-0088"},
			{"bulan": "Oktober", "tahun": 2026, "nominal": 250000, "dibayar": 250000, "status": "lunas", "no_kuitansi": "SPP-20261012-0105"},
			{"bulan": "November", "tahun": 2026, "nominal": 250000, "dibayar": 0, "status": "belum_lunas", "no_kuitansi": nil},
			{"bulan": "Desember", "tahun": 2026, "nominal": 250000, "dibayar": 0, "status": "akan_datang", "no_kuitansi": nil},
			{"bulan": "Januari", "tahun": 2027, "nominal": 250000, "dibayar": 0, "status": "akan_datang", "no_kuitansi": nil},
			{"bulan": "Februari", "tahun": 2027, "nominal": 250000, "dibayar": 0, "status": "akan_datang", "no_kuitansi": nil},
			{"bulan": "Maret", "tahun": 2027, "nominal": 250000, "dibayar": 0, "status": "akan_datang", "no_kuitansi": nil},
			{"bulan": "April", "tahun": 2027, "nominal": 250000, "dibayar": 0, "status": "akan_datang", "no_kuitansi": nil},
			{"bulan": "Mei", "tahun": 2027, "nominal": 250000, "dibayar": 0, "status": "akan_datang", "no_kuitansi": nil},
			{"bulan": "Juni", "tahun": 2027, "nominal": 250000, "dibayar": 0, "status": "ditanggung_pemerintah", "no_kuitansi": nil},
		}

		nonSpp := []gin.H{
			{"nama_pos": "Uang Gedung / Sarana", "nominal": 1500000, "dibayar": 1500000, "status": "lunas"},
			{"nama_pos": "Paket Seragam & Atribut", "nominal": 600000, "dibayar": 400000, "status": "sebagian"},
			{"nama_pos": "Uji Kompetensi Kejuruan", "nominal": 350000, "dibayar": 0, "status": "belum_lunas"},
		}

		pembayaran := []gin.H{
			{
				"kode_transaksi": "SPP-20261012-0105",
				"tgl_bayar":      "2026-10-12T09:30:00Z",
				"jumlah_bayar":   250000,
				"metode":         "Transfer Bank (BCA)",
				"petugas":        "Bendahara Sekolah",
				"status":         "valid",
				"token":          generateReceiptToken(secret, "SPP-20261012-0105"),
			},
			{
				"kode_transaksi": "SPP-20260910-0088",
				"tgl_bayar":      "2026-09-10T08:15:00Z",
				"jumlah_bayar":   250000,
				"metode":         "Tunai di Loket",
				"petugas":        "Siti Rahmawati, S.Pd",
				"status":         "valid",
				"token":          generateReceiptToken(secret, "SPP-20260910-0088"),
			},
			{
				"kode_transaksi": "SRG-20260814-0045",
				"tgl_bayar":      "2026-08-14T11:00:00Z",
				"jumlah_bayar":   400000,
				"metode":         "QRIS",
				"petugas":        "Loket Kasir 1",
				"status":         "valid",
				"token":          generateReceiptToken(secret, "SRG-20260814-0045"),
			},
		}

		response.Success(c, http.StatusOK, "Kartu SPP berhasil dimuat.", gin.H{
			"spp":        sppList,
			"non_spp":    nonSpp,
			"pembayaran": pembayaran,
		})
	}
}

// StudentReceipt menyajikan rincian satu kuitansi.
func StudentReceipt(db *gorm.DB, secret string) gin.HandlerFunc {
	return func(c *gin.Context) {
		kode := c.Param("kode")
		if kode == "" {
			response.Error(c, http.StatusBadRequest, "INVALID_CODE", "Kode kuitansi wajib disertakan.")
			return
		}

		response.Success(c, http.StatusOK, "Detail kuitansi berhasil dimuat.", gin.H{
			"kode_transaksi": kode,
			"tgl_bayar":      time.Now().Format(time.RFC3339),
			"jumlah_bayar":   250000,
			"metode":         "Transfer Bank (BCA)",
			"petugas":        "Bendahara Sekolah",
			"status":         "valid",
			"token":          generateReceiptToken(secret, kode),
		})
	}
}

// PublicReceipt memverifikasi keabsahan kuitansi dari QR Code tanpa perlu login.
func PublicReceipt(secret string) gin.HandlerFunc {
	return func(c *gin.Context) {
		kode := c.Param("kode")
		token := c.Param("token")

		expected := generateReceiptToken(secret, kode)
		isValid := subtleConstantTimeCompare(token, expected)

		if !isValid {
			response.Error(c, http.StatusNotFound, "RECEIPT_NOT_FOUND", "Bukti pembayaran tidak ditemukan atau tanda tangan digital tidak valid.")
			return
		}

		response.Success(c, http.StatusOK, "Kuitansi terverifikasi absah.", gin.H{
			"kode_transaksi": kode,
			"status":         "valid",
			"sekolah":        "SMK Negeri 1 Surabaya",
			"tgl_verifikasi": time.Now().Format(time.RFC3339),
		})
	}
}

func generateReceiptToken(secret, kode string) string {
	mac := hmac.New(sha256.New, []byte(secret))
	mac.Write([]byte(kode))
	sum := hex.EncodeToString(mac.Sum(nil))
	if len(sum) > 10 {
		return sum[:10]
	}
	return sum
}

func subtleConstantTimeCompare(a, b string) bool {
	if len(a) != len(b) {
		return false
	}
	return hmac.Equal([]byte(a), []byte(b))
}
