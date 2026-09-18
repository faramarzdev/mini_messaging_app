import { useEffect, useState, useRef } from "react";
import { useTheme } from "../context/ThemeContext";
import { useNavigate } from "react-router-dom";
import { useAuth } from "../context/AuthContext";
import ConversationItem from "./ConversationItem";
import ConversationsSkeleton from "./skeletons/ConversationsSkeleton";

export default function Sidebar({
  conversations,
  isConversationsLoading,
  conversationsErrors,
}) {
  const { dark, toggle: toggleDark } = useTheme();
  const navigate = useNavigate();

  const [search, setSearch] = useState("");
  const [menuOpen, setMenuOpen] = useState(false);
  const menuRef = useRef(null);
  const { user, logout } = useAuth();

  // Close menu when clicking outside
  useEffect(() => {
    function handleClick(e) {
      if (menuRef.current && !menuRef.current.contains(e.target)) {
        setMenuOpen(false);
      }
    }
    document.addEventListener("mousedown", handleClick);
    return () => document.removeEventListener("mousedown", handleClick);
  }, []);

  const logoutHandler = () => {
    setMenuOpen(false);
    logout();
    localStorage.removeItem("auth_token");
    window.location.href = "/auth/login";
  };

  return (
    <aside className="flex flex-col w-80 min-w-[280px] h-full bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-700">
      {/* ── Header ── */}
      <div className="flex items-center gap-2.5 px-3 py-3 border-b border-gray-200 dark:border-gray-700">
        {/* Hamburger + dropdown */}
        <div className="relative" ref={menuRef}>
          <button
            onClick={() => setMenuOpen((o) => !o)}
            className="w-9 h-9 flex items-center justify-center rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
            aria-label="Menu"
          >
            <HamburgerIcon />
          </button>

          {menuOpen && (
            <div className="absolute top-11 left-0 z-50 w-52 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg overflow-hidden">
              <MenuItem
                icon={<UserIcon />}
                label="My Profile"
                onClick={() => {
                  navigate("/profile/me");
                  setMenuOpen(false);
                }}
              />
              <MenuItem
                icon={<MoonIcon />}
                label="Dark Mode"
                onClick={() => {
                  toggleDark();
                  setMenuOpen(false);
                }}
                right={
                  <span
                    className={`text-xs px-2 py-0.5 rounded-full font-medium ${dark ? "bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300" : "bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400"}`}
                  >
                    {dark ? "ON" : "OFF"}
                  </span>
                }
              />
              <MenuItem
                icon={<NewChatIcon />}
                label="New Message"
                onClick={() => setMenuOpen(false)}
              />
              <div className="border-t border-gray-100 dark:border-gray-700" />
              <MenuItem
                icon={<LogoutIcon />}
                label="Log Out"
                onClick={logoutHandler}
                danger
              />
            </div>
          )}
        </div>

        {/* Search */}
        <div className="flex-1 flex items-center gap-2 bg-gray-100 dark:bg-gray-800 rounded-full px-3.5 py-2">
          <SearchIcon className="w-4 h-4 text-gray-400 flex-shrink-0" />
          <input
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Search"
            className="flex-1 bg-transparent text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 outline-none"
          />
          {search && (
            <button
              onClick={() => setSearch("")}
              className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            >
              <XIcon className="w-3.5 h-3.5" />
            </button>
          )}
        </div>
      </div>

      {/* ── Contact list ── */}
      <div className="flex-1 overflow-y-auto scrollbar-thin">
        {isConversationsLoading ? (
          <div className="mt-5 mx-3">
            <ConversationsSkeleton />
          </div>
        ) : conversationsErrors ? (
          <p className="text-center text-sm text-red-500 mt-12 px-6">
            Error loading conversations
          </p>
        ) : conversations.length ? (
          conversations.map((conversation) => (
            <ConversationItem
              key={conversation.id}
              conversation={conversation}
              loggedInUserId={user.id}
            />
          ))
        ) : (
          <p className="text-center text-sm text-gray-400 dark:text-gray-500 mt-12 px-6">
            No conversations found
          </p>
        )}
      </div>

      {/* ── Footer: compose button ── */}
      <div className="p-3 border-t border-gray-200 dark:border-gray-700 flex justify-end">
        <button
          className="w-12 h-12 bg-blue-600 hover:bg-blue-700 text-white rounded-full flex items-center justify-center shadow-md transition-colors"
          aria-label="New conversation"
        >
          <PencilIcon className="w-5 h-5" />
        </button>
      </div>
    </aside>
  );
}

// ─── Menu item ───────────────────────────────────────────────────────────────

function MenuItem({ icon, label, onClick, right, danger = false }) {
  return (
    <button
      onClick={onClick}
      className={`
        w-full flex items-center gap-3 px-4 py-2.5 text-sm transition-colors
        hover:bg-gray-50 dark:hover:bg-gray-700/60
        ${danger ? "text-red-500" : "text-gray-700 dark:text-gray-300"}
      `}
    >
      <span
        className={`w-5 h-5 flex-shrink-0 ${danger ? "text-red-500" : "text-gray-400 dark:text-gray-500"}`}
      >
        {icon}
      </span>
      <span className="flex-1">{label}</span>
      {right && <span>{right}</span>}
    </button>
  );
}

// ─── Inline SVG icons ─────────────────────────────────────────────────────────

const stroke = {
  fill: "none",
  stroke: "currentColor",
  strokeWidth: 2,
  strokeLinecap: "round",
  strokeLinejoin: "round",
};

function HamburgerIcon() {
  return (
    <svg className="w-5 h-5" viewBox="0 0 24 24" {...stroke}>
      <line x1="3" y1="6" x2="21" y2="6" />
      <line x1="3" y1="12" x2="21" y2="12" />
      <line x1="3" y1="18" x2="21" y2="18" />
    </svg>
  );
}
function SearchIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" {...stroke}>
      <circle cx="11" cy="11" r="8" />
      <line x1="21" y1="21" x2="16.65" y2="16.65" />
    </svg>
  );
}
function XIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" {...stroke}>
      <line x1="18" y1="6" x2="6" y2="18" />
      <line x1="6" y1="6" x2="18" y2="18" />
    </svg>
  );
}
function UserIcon() {
  return (
    <svg className="w-4 h-4" viewBox="0 0 24 24" {...stroke}>
      <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
      <circle cx="12" cy="7" r="4" />
    </svg>
  );
}
function MoonIcon() {
  return (
    <svg className="w-4 h-4" viewBox="0 0 24 24" {...stroke}>
      <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
    </svg>
  );
}
function NewChatIcon() {
  return (
    <svg className="w-4 h-4" viewBox="0 0 24 24" {...stroke}>
      <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
    </svg>
  );
}
function LogoutIcon() {
  return (
    <svg className="w-4 h-4" viewBox="0 0 24 24" {...stroke}>
      <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
      <polyline points="16 17 21 12 16 7" />
      <line x1="21" y1="12" x2="9" y2="12" />
    </svg>
  );
}
function GroupIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" {...stroke}>
      <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
      <circle cx="9" cy="7" r="4" />
      <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
      <path d="M16 3.13a4 4 0 0 1 0 7.75" />
    </svg>
  );
}
function PencilIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 24 24" {...stroke}>
      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
    </svg>
  );
}
