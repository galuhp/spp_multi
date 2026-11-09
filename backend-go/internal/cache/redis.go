package cache

import (
	"context"
	"time"

	"github.com/redis/go-redis/v9"
)

// RedisStore adalah implementasi CacheStore berbasis Redis. Gunakan ini untuk
// deployment multi-instance agar kunci idempotensi berlaku lintas proses.
type RedisStore struct {
	client *redis.Client
}

// NewRedisStore membungkus client Redis yang sudah ada.
func NewRedisStore(client *redis.Client) *RedisStore {
	return &RedisStore{client: client}
}

// NewRedisStoreFromAddr membuat client Redis baru.
func NewRedisStoreFromAddr(addr, password string, db int) *RedisStore {
	return NewRedisStore(redis.NewClient(&redis.Options{
		Addr:         addr,
		Password:     password,
		DB:           db,
		DialTimeout:  5 * time.Second,
		ReadTimeout:  3 * time.Second,
		WriteTimeout: 3 * time.Second,
		PoolSize:     20,
	}))
}

// SetNX menyimpan nilai hanya jika key belum ada.
func (s *RedisStore) SetNX(ctx context.Context, key, value string, ttl time.Duration) (bool, error) {
	return s.client.SetNX(ctx, key, value, ttl).Result()
}

// Delete menghapus key dari Redis.
func (s *RedisStore) Delete(ctx context.Context, key string) error {
	return s.client.Del(ctx, key).Err()
}

// Ping memeriksa koneksi Redis.
func (s *RedisStore) Ping(ctx context.Context) error {
	return s.client.Ping(ctx).Err()
}

// Close menutup koneksi Redis.
func (s *RedisStore) Close() error {
	return s.client.Close()
}
