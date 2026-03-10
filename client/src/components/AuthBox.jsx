export default function AuthBox({ title, children }) {
  return (
    <>
      <div className="min-h-[calc(100vh-16rem)] flex items-center justify-center px-4">
        <div className="w-full max-w-md">
          <div className="bg-white dark:bg-zinc-800 rounded-2xl border border-stone-200 dark:border-zinc-700 shadow-xl p-8">
            <p className="text-3xl font-bold text-stone-900 dark:text-stone-100 mb-5">
              {title}
            </p>
            {children}
          </div>
        </div>
      </div>
    </>
  );
}
