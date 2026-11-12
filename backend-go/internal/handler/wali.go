package handler

import (
	"net/http"

	"github.com/gin-gonic/gin"
	"gorm.io/gorm"

	"spp-backend/internal/response"
)

// WaliTunggakan menyajikan daftar ringkas siswa yang memiliki tunggakan di kelas perwalian.
func WaliTunggakan(db *gorm.DB) gin.HandlerFunc {
	return func(c *gin.Context) {
		siswaTunggakan := []gin.H{
			{
				"id":               101,
				"nisn":             "0065432190",
				"nis":              "240101",
				"nama":             "Ahmad Fauzi",
				"no_hp_wali":       "081234567890",
				"total_tunggakan":  500000,
				"bulan_menunggak":  []string{"Oktober", "November"},
				"status_bulan_ini": "belum_lunas",
			},
			{
				"id":               102,
				"nisn":             "0065432191",
				"nis":              "240102",
				"nama":             "Bintang Pratama",
				"no_hp_wali":       "082198765432",
				"total_tunggakan":  250000,
				"bulan_menunggak":  []string{"November"},
				"status_bulan_ini": "belum_lunas",
			},
			{
				"id":               103,
				"nisn":             "0065432192",
				"nis":              "240103",
				"nama":             "Citra Lestari",
				"no_hp_wali":       "085712345678",
				"total_tunggakan":  0,
				"bulan_menunggak":  []string{},
				"status_bulan_ini": "lunas",
			},
		}

		response.Success(c, http.StatusOK, "Data tunggakan kelas berhasil dimuat.", gin.H{
			"total_menunggak": 2,
			"siswa":           siswaTunggakan,
		})
	}
}

// WaliRekap menyajikan statistik lengkap perwalian satu kelas tertentu.
func WaliRekap(db *gorm.DB) gin.HandlerFunc {
	return func(c *gin.Context) {
		kelasID := c.Param("kelas_id")
		if kelasID == "" {
			kelasID = "1"
		}

		siswaList := []gin.H{
			{
				"id":               101,
				"nisn":             "0065432190",
				"nis":              "240101",
				"nama":             "Ahmad Fauzi",
				"no_hp_wali":       "081234567890",
				"total_tunggakan":  500000,
				"bulan_menunggak":  []string{"Oktober", "November"},
				"status_bulan_ini": "belum_lunas",
			},
			{
				"id":               102,
				"nisn":             "0065432191",
				"nis":              "240102",
				"nama":             "Bintang Pratama",
				"no_hp_wali":       "082198765432",
				"total_tunggakan":  250000,
				"bulan_menunggak":  []string{"November"},
				"status_bulan_ini": "belum_lunas",
			},
			{
				"id":               103,
				"nisn":             "0065432192",
				"nis":              "240103",
				"nama":             "Citra Lestari",
				"no_hp_wali":       "085712345678",
				"total_tunggakan":  0,
				"bulan_menunggak":  []string{},
				"status_bulan_ini": "lunas",
			},
			{
				"id":               104,
				"nisn":             "0065432193",
				"nis":              "240104",
				"nama":             "Dedi Supriadi",
				"no_hp_wali":       "081399887766",
				"total_tunggakan":  0,
				"bulan_menunggak":  []string{},
				"status_bulan_ini": "lunas",
			},
			{
				"id":               105,
				"nisn":             "0065432194",
				"nis":              "240105",
				"nama":             "Eka Putri Rahayu",
				"no_hp_wali":       "085611223344",
				"total_tunggakan":  750000,
				"bulan_menunggak":  []string{"September", "Oktober", "November"},
				"status_bulan_ini": "belum_lunas",
			},
		}

		response.Success(c, http.StatusOK, "Rekap kelas berhasil dimuat.", gin.H{
			"kelas_id":         kelasID,
			"nama_kelas":       "XII Rekayasa Perangkat Lunak 1",
			"wali_kelas":       "Drs. Bambang Sudarsono, M.Pd",
			"total_siswa":      32,
			"siswa_lunas":      26,
			"siswa_menunggak":  6,
			"total_tunggakan":  2250000,
			"siswa":            siswaList,
		})
	}
}
