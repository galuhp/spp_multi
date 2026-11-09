package middleware

import (
	"log/slog"
	"strings"

	"github.com/gin-gonic/gin"

	"spp-backend/internal/response"
)

const tierUpgradeMessage = "Fitur ini hanya tersedia pada paket E-SPP Pro. Silakan tingkatkan lisensi Anda."

// EnsureProTier menjaga rute agar hanya dapat diakses pada lisensi Pro.
//
// Tanpa featureKey, middleware hanya memeriksa tier aktif. Dengan satu atau
// lebih featureKey, seluruh fitur tersebut harus tersedia pada lisensi aktif.
func EnsureProTier(tierChecker LicenseChecker, featureKeys ...string) gin.HandlerFunc {
	return func(c *gin.Context) {
		if tierChecker == nil {
			slog.Error("license checker belum dikonfigurasi", "path", c.Request.URL.Path)
			abortLicenseCheckFailed(c)

			return
		}

		ctx := c.Request.Context()

		features := make([]string, 0, len(featureKeys))
		for _, featureKey := range featureKeys {
			if trimmed := strings.TrimSpace(featureKey); trimmed != "" {
				features = append(features, trimmed)
			}
		}

		if len(features) == 0 {
			tier, err := tierChecker.Tier(ctx)
			if err != nil {
				slog.Error("gagal membaca tier lisensi", "error", err, "path", c.Request.URL.Path)
				abortLicenseCheckFailed(c)

				return
			}

			if !strings.EqualFold(strings.TrimSpace(tier), "pro") {
				abortTierUpgrade(c)

				return
			}

			c.Next()

			return
		}

		for _, featureKey := range features {
			allowed, err := tierChecker.HasFeature(ctx, featureKey)
			if err != nil {
				slog.Error(
					"gagal memeriksa fitur lisensi",
					"error", err,
					"feature", featureKey,
					"path", c.Request.URL.Path,
				)
				abortLicenseCheckFailed(c)

				return
			}

			if !allowed {
				abortTierUpgrade(c)

				return
			}
		}

		c.Next()
	}
}

func abortTierUpgrade(c *gin.Context) {
	response.Abort(c, 403, response.CodeTierUpgradeRequired, tierUpgradeMessage)
}

func abortLicenseCheckFailed(c *gin.Context) {
	response.Abort(
		c,
		500,
		response.CodeLicenseCheckFailed,
		"Tidak dapat memverifikasi lisensi paket saat ini. Silakan coba lagi.",
	)
}
