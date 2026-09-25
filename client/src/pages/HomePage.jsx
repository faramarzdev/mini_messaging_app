import { Link } from "react-router-dom";
import { useTheme } from "../context/ThemeContext";
import { ROUTES } from "../routes/paths.js";

export default function HomePage() {
  const { dark, toggle } = useTheme();

  return (
    <div className="h-full overflow-y-auto bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100">
      <header className="sticky top-0 z-10 backdrop-blur bg-gray-50/80 dark:bg-gray-950/80 border-b border-stone-200 dark:border-gray-800">
        <div className="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
          <div className="flex items-center gap-2.5">
            <div className="w-9 h-9 rounded-xl bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
              <ChatIcon className="w-5 h-5" />
            </div>
            <span className="font-bold text-lg tracking-tight">Chat</span>
          </div>

          <div className="flex items-center gap-2">
            <button
              type="button"
              onClick={toggle}
              aria-label="Toggle theme"
              className="w-9 h-9 flex items-center justify-center rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-200/60 dark:hover:bg-gray-800 transition-colors"
            >
              {dark ? (
                <SunIcon className="w-4 h-4" />
              ) : (
                <MoonIcon className="w-4 h-4" />
              )}
            </button>
            <Link
              to={ROUTES.authLogin}
              className="px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
            >
              Log in
            </Link>
            <Link
              to={ROUTES.authRegister}
              className="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white text-sm font-semibold shadow-sm shadow-indigo-200/50 dark:shadow-indigo-900/30 transition-colors"
            >
              Sign up
            </Link>
          </div>
        </div>
      </header>


      <section className="max-w-3xl mx-auto px-6 pt-20 pb-14 text-center">
        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-xs font-semibold mb-6">
          <span className="w-1.5 h-1.5 rounded-full bg-indigo-500" />
          Laravel API &middot; React client &middot; real-time via Reverb
        </div>

        <h1 className="text-4xl sm:text-5xl font-bold tracking-tight leading-[1.1]">
          Conversations that
          <span className="text-indigo-600 dark:text-indigo-400"> keep up</span>
          <br />
          with you
        </h1>

        <p className="mt-5 text-lg text-gray-500 dark:text-gray-400 max-w-xl mx-auto">
          Direct messages, group channels and read receipts, delivered over
          WebSockets and backed by a Laravel API built for scale.
        </p>

        <div className="mt-8 flex items-center justify-center gap-3">
          <Link
            to={ROUTES.authRegister}
            className="px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-semibold shadow-sm shadow-indigo-200/50 dark:shadow-indigo-900/30 transition-colors flex items-center gap-2"
          >
            Create an account
            <ArrowRightIcon className="w-4 h-4" />
          </Link>
          <Link
            to={ROUTES.authLogin}
            className="px-6 py-3 rounded-xl border border-stone-200 dark:border-gray-700 font-semibold text-gray-700 dark:text-gray-200 hover:bg-white dark:hover:bg-gray-800 transition-colors"
          >
            Log in
          </Link>
        </div>
      </section>

      {/* ── Product preview (static mock, no real data) ── */}
      <section className="max-w-3xl mx-auto px-6 pb-20">
        <div className="rounded-2xl border border-stone-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-xl overflow-hidden">
          <div className="flex items-center gap-1.5 px-4 py-3 border-b border-stone-200 dark:border-gray-700">
            <span className="w-2.5 h-2.5 rounded-full bg-red-400/70" />
            <span className="w-2.5 h-2.5 rounded-full bg-amber-400/70" />
            <span className="w-2.5 h-2.5 rounded-full bg-emerald-400/70" />
            <span className="ml-3 text-xs font-medium text-gray-400 dark:text-gray-500">
              #design-team
            </span>
          </div>

          <div className="p-5 sm:p-6 space-y-4 bg-gray-50/60 dark:bg-gray-900/40">
            <ChatBubble
              name="Alex"
              initial="A"
              message="Pushed the API changes for the unified inbox 🎉"
              time="9:41"
            />
            <ChatBubble
              name="Priya"
              initial="P"
              message="Nice — pulling it down now, running the test suite."
              time="9:42"
            />
            <ChatBubble
              self
              message="All green ✅ ready to merge."
              time="9:44"
            />
          </div>
        </div>
      </section>

      {/* ── Features ── */}
      <section className="max-w-5xl mx-auto px-6 pb-20">
        <div className="grid sm:grid-cols-3 gap-5">
          <FeatureCard
            icon={<BoltIcon className="w-5 h-5" />}
            title="Real-time delivery"
            description="Messages, typing indicators and read receipts stream instantly over WebSockets."
          />
          <FeatureCard
            icon={<UsersIcon className="w-5 h-5" />}
            title="Channels & groups"
            description="Public channels, private groups and one-on-one conversations, all in one inbox."
          />
          <FeatureCard
            icon={<ShieldIcon className="w-5 h-5" />}
            title="Built to scale"
            description="Token-based auth, policy-backed authorization, and a queryable REST API underneath."
          />
        </div>
      </section>

      {/* ── Footer ── */}
      <footer className="border-t border-stone-200 dark:border-gray-800">
        <div className="max-w-6xl mx-auto px-6 py-8 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-gray-400 dark:text-gray-500">
          <span>
            &copy; {new Date().getFullYear()} Chat — a portfolio project by
            Faramarz
          </span>
          <a
            href="https://github.com/faramarzdev/mini_messaging_app"
            target="_blank"
            rel="noreferrer"
            className="flex items-center gap-1.5 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
          >
            <GithubIcon className="w-4 h-4" />
            View source
          </a>
        </div>
      </footer>
    </div>
  );
}

// ─── Small presentational pieces ───────────────────────────────────────────

function ChatBubble({ name, initial, message, time, self = false }) {
  if (self) {
    return (
      <div className="flex justify-end">
        <div className="max-w-[75%]">
          <div className="px-4 py-2.5 rounded-2xl rounded-br-md bg-indigo-600 text-white text-sm">
            {message}
          </div>
          <div className="mt-1 text-right text-xs text-gray-400 dark:text-gray-500">
            {time}
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="flex items-start gap-2.5">
      <div className="w-8 h-8 shrink-0 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
        <span className="text-xs font-bold text-gray-600 dark:text-gray-300">
          {initial}
        </span>
      </div>
      <div className="max-w-[75%]">
        <div className="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">
          {name}
        </div>
        <div className="px-4 py-2.5 rounded-2xl rounded-tl-md bg-white dark:bg-gray-700/60 border border-stone-200 dark:border-gray-700 text-sm text-gray-800 dark:text-gray-100">
          {message}
        </div>
        <div className="mt-1 text-xs text-gray-400 dark:text-gray-500">
          {time}
        </div>
      </div>
    </div>
  );
}

function FeatureCard({ icon, title, description }) {
  return (
    <div className="p-5 rounded-2xl border border-stone-200 dark:border-gray-700 bg-white dark:bg-gray-800">
      <div className="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mb-3">
        {icon}
      </div>
      <h3 className="font-semibold tracking-tight mb-1">{title}</h3>
      <p className="text-sm text-gray-500 dark:text-gray-400">{description}</p>
    </div>
  );
}

// ─── Inline SVG icons (matches the stroke style used across the app) ──────

const stroke = {
  fill: "none",
  stroke: "currentColor",
  strokeWidth: 2,
  strokeLinecap: "round",
  strokeLinejoin: "round",
};

function ChatIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" {...stroke}>
      <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
    </svg>
  );
}

function MoonIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" {...stroke}>
      <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
    </svg>
  );
}

function SunIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" {...stroke}>
      <circle cx="12" cy="12" r="4" />
      <path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41m11.32-11.32l1.41-1.41" />
    </svg>
  );
}

function ArrowRightIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" {...stroke}>
      <path d="M13 7l5 5m0 0l-5 5m5-5H6" />
    </svg>
  );
}

function BoltIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" {...stroke}>
      <path d="M13 2L3 14h7l-1 8 10-12h-7l1-8z" />
    </svg>
  );
}

function UsersIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" {...stroke}>
      <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
      <circle cx="9" cy="7" r="4" />
      <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
      <path d="M16 3.13a4 4 0 0 1 0 7.75" />
    </svg>
  );
}

function ShieldIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" {...stroke}>
      <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
    </svg>
  );
}

function GithubIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="currentColor">
      <path d="M12 .5C5.65.5.5 5.65.5 12c0 5.09 3.29 9.4 7.86 10.93.58.1.79-.25.79-.56 0-.28-.01-1.02-.02-2-3.2.7-3.88-1.54-3.88-1.54-.52-1.34-1.28-1.7-1.28-1.7-1.05-.72.08-.7.08-.7 1.16.08 1.77 1.19 1.77 1.19 1.03 1.77 2.7 1.26 3.36.96.1-.75.4-1.26.73-1.55-2.55-.29-5.24-1.28-5.24-5.69 0-1.26.45-2.29 1.19-3.09-.12-.29-.52-1.46.11-3.05 0 0 .97-.31 3.18 1.18a11 11 0 0 1 5.79 0c2.2-1.49 3.17-1.18 3.17-1.18.64 1.59.24 2.76.12 3.05.74.8 1.19 1.83 1.19 3.09 0 4.42-2.69 5.4-5.25 5.68.41.36.78 1.07.78 2.15 0 1.56-.01 2.81-.01 3.19 0 .31.21.67.8.56A10.52 10.52 0 0 0 23.5 12c0-6.35-5.15-11.5-11.5-11.5z" />
    </svg>
  );
}
