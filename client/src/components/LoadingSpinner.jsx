export default function LoadingSpinner({ size = "sm" }) {
  const sizes = {
    sm: "h-4 w-4",
    md: "h-6 w-6",
    lg: "h-8 w-8",
    xl: "h-12 w-12",
    xxl: "h-24 w-24",
    exl: "h-64 w-64",
  };

  return (
    <div
      className={`${sizes[size]} animate-spin rounded-full border-2 border-gray-300 border-t-amber-600`}
    />
  );
}
