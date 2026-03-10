import { useState } from "react";
import { useAuth } from "../../context/AuthContext";
import { useNavigate } from "react-router-dom";
import ChatProfile from "../../components/app/ChatProfile";

export default function Topbar({ darkMode }) {
  const [userMenuOpen, setUserMenuOpen] = useState(false);
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = async () => {
    await logout();
    navigate("/login");
  };

  const initials = user?.name
    ? user.name
        .split(" ")
        .map((n) => n[0])
        .join("")
        .toUpperCase()
        .slice(0, 2)
    : "A";

  return (
    <header
      className={`
          flex items-center justify-between h-16 px-5 shrink-0 gap-4
          ${
            darkMode
              ? "bg-slate-950 border-b border-slate-800"
              : "bg-white border-b border-slate-200"
          }
        `}
    >
      {/* Left: Welcome message */}
      <div className="flex items-center gap-2 min-w-0">
        <div
          className={`h-4 w-px ${darkMode ? "bg-slate-700" : "bg-slate-200"}`}
        />
        <span
          className={`text-sm font-medium hidden sm:block ${darkMode ? "text-slate-400" : "text-slate-400"}`}
        >
          Welcome back, {user?.name?.split(" ")[0] ?? "Admin"} 👋
        </span>
      </div>

      {/* Right: user menu */}
      <div className="relative">
        <button
          onClick={() => setUserMenuOpen((o) => !o)}
          className={`
                flex items-center gap-2.5 px-2 py-1.5 rounded-xl transition-all
                ${
                  darkMode
                    ? "hover:bg-slate-800 border border-transparent hover:border-slate-700"
                    : "hover:bg-slate-50 border border-transparent hover:border-slate-200"
                }
              `}
        >
          <div className="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-xs font-bold shrink-0 shadow-sm">
            {initials}
          </div>
          <div className="hidden sm:block text-left">
            <p
              className={`text-sm font-semibold leading-tight truncate max-w-[130px] ${darkMode ? "text-slate-200" : "text-slate-700"}`}
            >
              {user?.name ?? "Admin"}
            </p>
            <p
              className={`text-xs truncate max-w-[130px] ${darkMode ? "text-slate-500" : "text-slate-400"}`}
            >
              {user?.role ?? "Administrator"}
            </p>
          </div>
          <svg
            className={`w-4 h-4 hidden sm:block transition-transform duration-200 ${userMenuOpen ? "rotate-180" : ""} ${darkMode ? "text-slate-500" : "text-slate-400"}`}
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M19 9l-7 7-7-7"
            />
          </svg>
        </button>

        {/* Dropdown */}
        {userMenuOpen && (
          <>
            <div
              className="fixed inset-0 z-10"
              onClick={() => setUserMenuOpen(false)}
            />
            <div
              className={`
                  absolute right-0 mt-2 w-52 rounded-xl shadow-lg z-20 overflow-hidden
                  ${
                    darkMode
                      ? "bg-slate-800 ring-1 ring-slate-700/60"
                      : "bg-white ring-1 ring-slate-200/80"
                  }
                `}
            >
              {/* User info header */}
              <div
                className={`px-4 py-3
                    ${
                      darkMode
                        ? "border-b border-slate-700 bg-slate-900/50"
                        : "border-b border-slate-100 bg-slate-50"
                    }`}
              >
                <ChatProfile user={user} />
              </div>

              {/* Actions */}
              <div className="py-1.5">
                <button
                  onClick={handleLogout}
                  className={`
                        flex items-center gap-2.5 w-full px-4 py-2.5 text-sm font-medium transition-colors
                        ${
                          darkMode
                            ? "text-red-400 hover:bg-red-500/10"
                            : "text-red-600 hover:bg-red-50"
                        }
                      `}
                >
                  <svg
                    className="w-4 h-4 shrink-0"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                    />
                  </svg>
                  Sign out
                </button>
              </div>
            </div>
          </>
        )}
      </div>
    </header>
  );
}
