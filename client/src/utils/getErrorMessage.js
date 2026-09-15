/**
 * Extracts a user-facing message from an Axios error thrown by the API layer.
 *
 * Laravel error responses land in `err.response.data.message` — Axios never
 * puts a `message` field directly on `err.response` itself (that was a bug
 * in the old per-page error handling: `err.response?.message` is always
 * undefined and was silently falling through to the correct check anyway).
 */
export function getErrorMessage(
  err,
  fallback = "Something went wrong. Please try again.",
) {
  return err.response?.data?.message || fallback;
}
