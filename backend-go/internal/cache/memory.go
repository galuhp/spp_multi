// Package cache menyediakan implementasi CacheStore untuk Redis dan memori.
package cache

import (
	"context"
	"sync"
	"time"
)

// MemoryStore adalah cache in-memory yang aman untuk digunakan bersamaan.
// Cocok untuk pengembangan lokal atau deployment satu instance.
type MemoryStore struct {
	mu    sync.Mutex
	items map[string]memoryItem
	ops   uint64
	now   func() time.Time
}

type memoryItem struct {
	value     string
	expiresAt time.Time
}

// NewMemoryStore membuat cache in-memory kosong.
func NewMemoryStore() *MemoryStore {
	return &MemoryStore{
		items: make(map[string]memoryItem),
		now:   time.Now,
	}
}

// SetNX menyimpan nilai hanya jika key belum ada atau sudah kedaluwarsa.
func (s *MemoryStore) SetNX(ctx context.Context, key, value string, ttl time.Duration) (bool, error) {
	if err := ctx.Err(); err != nil {
		return false, err
	}

	now := s.now()

	s.mu.Lock()
	defer s.mu.Unlock()

	if existing, exists := s.items[key]; exists {
		if existing.expiresAt.IsZero() || existing.expiresAt.After(now) {
			return false, nil
		}

		delete(s.items, key)
	}

	item := memoryItem{value: value}
	if ttl > 0 {
		item.expiresAt = now.Add(ttl)
	}
	s.items[key] = item

	s.ops++
	if s.ops%256 == 0 {
		s.pruneLocked(now)
	}

	return true, nil
}

// Delete menghapus key dari cache.
func (s *MemoryStore) Delete(ctx context.Context, key string) error {
	if err := ctx.Err(); err != nil {
		return err
	}

	s.mu.Lock()
	defer s.mu.Unlock()

	delete(s.items, key)

	return nil
}

// Len mengembalikan jumlah entri yang masih tersimpan. Berguna untuk observasi
// dan pengujian.
func (s *MemoryStore) Len() int {
	s.mu.Lock()
	defer s.mu.Unlock()

	return len(s.items)
}

func (s *MemoryStore) pruneLocked(now time.Time) {
	for key, item := range s.items {
		if !item.expiresAt.IsZero() && !now.Before(item.expiresAt) {
			delete(s.items, key)
		}
	}
}
