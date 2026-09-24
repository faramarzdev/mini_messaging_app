import { useNavigate } from "react-router-dom";
import Avatar from "./Avatar";
import { ROUTES } from "../routes/paths";

export default function ChatHeader({ profile, onBack }) {
  const navigate = useNavigate();

  return (
    <header className="flex items-center gap-3 px-4 py-2.5 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
      <button
        onClick={onBack}
        className="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors p-1 -ml-1 rounded-lg"
        aria-label="Back"
      >
        <BackIcon className="w-5 h-5" />
      </button>

      <button
        className="flex items-center gap-3 flex-1 min-w-0 text-left"
        onClick={() => navigate(ROUTES.profilePath(profile.handle))}
      >
        <Avatar profile={profile} />
        <div className="min-w-0">
          <p className="font-semibold text-sm text-gray-900 dark:text-gray-100 truncate">
            {profile.name}
          </p>
        </div>
      </button>

      <div className="flex items-center gap-1">
        <HeaderIconButton aria-label="Voice call">
          <PhoneIcon className="w-5 h-5" />
        </HeaderIconButton>
        <HeaderIconButton aria-label="Video call">
          <VideoIcon className="w-5 h-5" />
        </HeaderIconButton>
        <HeaderIconButton aria-label="Search in chat">
          <SearchIcon className="w-5 h-5" />
        </HeaderIconButton>
        <HeaderIconButton aria-label="More options">
          <DotsIcon className="w-5 h-5" />
        </HeaderIconButton>
      </div>
    </header>
  );
}

function HeaderIconButton({ children, ...props }) {
  return (
    <button
      className="w-9 h-9 flex items-center justify-center rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
      {...props}
    >
      {children}
    </button>
  );
}

// ─── Icons ────────────────────────────────────────────────────────────────────
const s = {
  fill: "none",
  stroke: "currentColor",
  strokeWidth: 2,
  strokeLinecap: "round",
  strokeLinejoin: "round",
};

function BackIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" {...s}>
      <polyline points="15 18 9 12 15 6" />
    </svg>
  );
}
function PhoneIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" {...s}>
      <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.18 2 2 0 0 1 3.6 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.6a16 16 0 0 0 6.29 6.29l.96-.96a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z" />
    </svg>
  );
}
function VideoIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" {...s}>
      <polygon points="23 7 16 12 23 17 23 7" />
      <rect x="1" y="5" width="15" height="14" rx="2" ry="2" />
    </svg>
  );
}
function SearchIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" {...s}>
      <circle cx="11" cy="11" r="8" />
      <line x1="21" y1="21" x2="16.65" y2="16.65" />
    </svg>
  );
}
function DotsIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" {...s}>
      <circle cx="12" cy="5" r="1" fill="currentColor" />
      <circle cx="12" cy="12" r="1" fill="currentColor" />
      <circle cx="12" cy="19" r="1" fill="currentColor" />
    </svg>
  );
}
