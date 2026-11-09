package main

import (
	"flag"
	"fmt"
	"os"
	"strings"
	"time"

	"spp-backend/internal/auth"
)

func main() {
	var (
		userID   = flag.Uint("user", 1, "ID pengguna")
		username = flag.String("username", "admin", "username pengguna")
		role     = flag.String("role", "admin", "role: admin, petugas, wali_kelas, siswa")
		siswaID  = flag.Uint("siswa", 0, "ID siswa (opsional, 0 = kosong)")
		ttl      = flag.Duration("ttl", 12*time.Hour, "masa berlaku token")
		secret   = flag.String("secret", os.Getenv("JWT_SECRET"), "JWT secret (default: env JWT_SECRET)")
	)

	flag.Parse()

	*role = strings.ToLower(strings.TrimSpace(*role))
	switch *role {
	case "admin", "petugas", "wali_kelas", "siswa":
	default:
		fmt.Fprintln(os.Stderr, "role tidak valid; gunakan admin, petugas, wali_kelas, atau siswa")
		os.Exit(1)
	}

	if len(strings.TrimSpace(*secret)) < 32 {
		fmt.Fprintln(os.Stderr, "JWT secret wajib diisi minimal 32 karakter")
		os.Exit(1)
	}

	var siswaPointer *uint
	if *siswaID != 0 {
		id := *siswaID
		siswaPointer = &id
	}

	manager := auth.NewTokenManager(*secret, *ttl)

	token, expiresAt, err := manager.Issue(*userID, *username, *role, siswaPointer)
	if err != nil {
		fmt.Fprintln(os.Stderr, "gagal membuat token:", err)
		os.Exit(1)
	}

	fmt.Fprintf(os.Stderr, "token berlaku sampai %s\n", expiresAt.Format(time.RFC3339))
	fmt.Println(token)
}
