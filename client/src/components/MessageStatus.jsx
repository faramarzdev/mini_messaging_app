export default function MessageStatus({ is_read }) {
  if (is_read) {
    return (
      <svg
        className="inline w-4 h-4 text-blue-500"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        strokeWidth="2.5"
        strokeLinecap="round"
        strokeLinejoin="round"
      >
        <polyline points="1 12 5 16 11 8" />
        <polyline points="8 12 12 16 18 8" />
      </svg>
    );
  } else {
    return (
      <svg
        className="inline w-4 h-4 text-gray-400 dark:text-gray-500"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        strokeWidth="2.5"
        strokeLinecap="round"
        strokeLinejoin="round"
      >
        <polyline points="1 12 5 16 11 8" />
        <polyline points="8 12 12 16 18 8" />
      </svg>
    );
  }
}
