BINARY := bin/spp-api
GO ?= go
LDFLAGS := -s -w

.PHONY: build run test test-race fmt vet tidy token

build:
	$(GO) build -trimpath -ldflags "$(LDFLAGS)" -o $(BINARY) ./cmd/api

run:
	$(GO) run ./cmd/api

test:
	$(GO) test ./... -count=1

test-race:
	$(GO) test -race ./... -count=1

fmt:
	gofmt -w .

vet:
	$(GO) vet ./...

tidy:
	$(GO) mod tidy

# Contoh: make token USER=1 ROLE=admin
token:
	$(GO) run ./cmd/token -user $(or $(USER),1) -role $(or $(ROLE),admin) -username $(or $(USERNAME),admin)
