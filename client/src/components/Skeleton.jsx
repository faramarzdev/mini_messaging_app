export default function Skeleton({ className = "" }) {
  return (
    <div
      className={`animate-pulse bg-gray-100 dark:bg-amber-900 rounded ${className}`}
    />
  );
}
