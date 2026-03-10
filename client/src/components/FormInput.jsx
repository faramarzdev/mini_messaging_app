export default function FormInput({ label, error, ...props }) {
  return (
    <div>
      {label && (
        <label className="block text-sm font-medium text-gray-800 mb-1 dark:text-gray-200">
          {label}
        </label>
      )}
      <input
        className={`w-full px-4 py-3 rounded-lg border border-stone-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-stone-900 dark:text-stone-100 placeholder-stone-400 dark:placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:focus:ring-amber-400 focus:border-transparent transition-all"
                    ${error ? "border-red-500 bg-red-50" : "border-gray-300"}`}
        {...props}
      />
      {error && <p className="text-red-500 text-xs mt-1">{error}</p>}
    </div>
  );
}
