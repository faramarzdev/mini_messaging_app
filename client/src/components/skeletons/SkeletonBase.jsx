export default function SkeletonBase({ className = "" }) {
  return (
    <div
      className={`animate-pulse bg-gray-400 dark:bg-gray-500 rounded ${className}`}
    />
  );
}
