export default function Pagination({ meta, onPageChange, loading = false }) {
  if (!meta || meta.total_page <= 1) return null;

  const pages = [...Array(meta.total_page).keys()].map((n) => n + 1);

  return (
    <div className="flex items-center gap-2 my-8">
      <button
        disabled={loading || meta.current_page === 1}
        onClick={() => onPageChange(meta.current_page - 1)}
        className="px-3 py-1 border rounded disabled:opacity-50"
      >
        Prev
      </button>

      {pages.map((page) => (
        <button
          disabled={loading}
          key={page}
          onClick={() => onPageChange(page)}
          className={`px-3 py-1 border rounded ${
            page === meta.current_page
              ? "bg-amber-500 bg-gradient-to-tl font-extrabold"
              : "hover:bg-amber-500 bg-gradient-to-tl"
          }`}
        >
          {page}
        </button>
      ))}

      <button
        disabled={loading || meta.current_page === meta.total_page}
        onClick={() => onPageChange(meta.current_page + 1)}
        className="px-3 py-1 border rounded disabled:opacity-50"
      >
        Next
      </button>
    </div>
  );
}
