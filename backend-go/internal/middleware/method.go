package middleware

// isMutatingMethod mengembalikan true untuk metode yang mengubah state.
func isMutatingMethod(method string) bool {
	switch method {
	case "POST", "PUT", "PATCH", "DELETE":
		return true
	default:
		return false
	}
}
