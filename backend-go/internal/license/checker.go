// Package license menyediakan pemeriksa lisensi paket Basic/Pro.
package license

import (
	"context"
	"strings"
)

// Tier adalah segmen lisensi aplikasi.
type Tier string

const (
	// TierBasic adalah paket dasar.
	TierBasic Tier = "basic"
	// TierPro adalah paket dengan seluruh fitur.
	TierPro Tier = "pro"
)

// NormalizeTier memetakan nilai konfigurasi ke Tier yang dikenal.
// Nilai pro/enterprise/premium dianggap Pro; selain itu Basic.
func NormalizeTier(raw string) Tier {
	switch strings.ToLower(strings.TrimSpace(raw)) {
	case "pro", "enterprise", "premium":
		return TierPro
	default:
		return TierBasic
	}
}

// StaticChecker membaca tier dan feature flag dari konfigurasi yang sudah
// dimuat. Semantiknya mengikuti App\Support\LisensiPaket pada backend Laravel:
//   - tier Pro membuka seluruh fitur;
//   - tier Basic hanya membuka fitur yang dipetakan ke "basic";
//   - fitur yang tidak dikenal dianggap khusus Pro.
type StaticChecker struct {
	tier     Tier
	features map[string]string
}

// NewStaticChecker membuat pemeriksa lisensi statis.
func NewStaticChecker(tier string, features map[string]string) *StaticChecker {
	copied := make(map[string]string, len(features))
	for key, value := range features {
		copied[key] = value
	}

	return &StaticChecker{
		tier:     NormalizeTier(tier),
		features: copied,
	}
}

// Tier mengembalikan tier aktif.
func (s *StaticChecker) Tier(_ context.Context) (string, error) {
	return string(s.tier), nil
}

// HasFeature memeriksa apakah fitur tersedia pada tier aktif.
func (s *StaticChecker) HasFeature(_ context.Context, feature string) (bool, error) {
	if s.tier == TierPro {
		return true, nil
	}

	required, exists := s.features[strings.TrimSpace(feature)]
	if !exists {
		return false, nil
	}

	return NormalizeTier(required) == TierBasic, nil
}
