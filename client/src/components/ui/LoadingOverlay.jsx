export default function LoadingOverlay({ message = "Loading..." }) {
  return (
    <div className="fixed inset-0 bg-gray-900/50 dark:bg-gray-900/70 backdrop-blur-sm flex items-center justify-center z-50">
      <div className="bg-white dark:bg-gray-800 rounded-2xl border border-stone-200 dark:border-gray-700 shadow-xl p-8 max-w-sm w-full">
        <div className="flex flex-col items-center space-y-4">
          <div className="animate-spin rounded-full h-28 w-28 border-8 border-indigo-600 border-t-transparent dark:border-indigo-400 dark:border-t-transparent"></div>

          <p className="text-gray-700 dark:text-gray-300 font-bold text-xl ">
            {message}
          </p>

          {/* <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
            <div className="bg-indigo-600 dark:bg-indigo-400 h-full rounded-full animate-pulse w-3/4"></div>
          </div> */}
        </div>
      </div>
    </div>
  );
}
