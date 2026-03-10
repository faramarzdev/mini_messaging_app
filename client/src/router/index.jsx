import { Routes, Route, Navigate, useLocation } from "react-router-dom";
import { useAuth } from "../context/AuthContext";

// Layouts
import PublicLayout from "../layouts/PublicLayout";
import AppLayout from "../layouts/AppLayout";

// Public pages
import HomePage from "../pages/Home";
import Login from "../pages/Login";
import Register from "../pages/Register";
import Error404 from "../pages/errors/404";
import Chat from "../pages/app/Chat";

// ---------------------------------------------------------------------------
// Guard: RequireAuth
// Redirects unauthenticated users to /login, preserving the intended path
// so they can be sent back after logging in.
// ---------------------------------------------------------------------------
export function RequireAuth({ children }) {
  const { isAuthenticated, loading } = useAuth();
  const location = useLocation();

  if (loading) return null; // AuthContext is still resolving — render nothing

  if (!isAuthenticated) {
    return <Navigate to="/login" state={{ from: location }} replace />;
  }

  return children;
}

// ---------------------------------------------------------------------------
// Guard: RequireRole
// Accepts a `roles` array prop (e.g. roles={["admin", "editor"]}).
// Redirects to "/" if the authenticated user's role isn't in the list.
// Always nest inside <RequireAuth> so `user` is guaranteed to be non-null.
// ---------------------------------------------------------------------------
export function RequireRole({ roles, children }) {
  const { user } = useAuth();

  if (!user || !roles.includes(user.role)) {
    return <Navigate to="/" replace />;
  }

  return children;
}

// ---------------------------------------------------------------------------
// AppRoutes — single export consumed by App.jsx
// ---------------------------------------------------------------------------
export default function AppRoutes() {
  return (
    <Routes>
      {/* ── Public routes ─────────────────────────────────────────────── */}
      <Route element={<PublicLayout />}>
        <Route path="/" element={<HomePage />} />
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />
      </Route>

      {/* ── App routes ──────────────────────────────────────────────── */}
      {/* Both guards wrap the entire /app subtree */}
      <Route
        path="/app"
        element={
          <RequireAuth>
            <AppLayout />
          </RequireAuth>
        }
      >
        <Route path="chat/:id?" element={<Chat />} />
      </Route>

      <Route
        path="*"
        element={
          <PublicLayout>
            <Error404 />
          </PublicLayout>
        }
      />
    </Routes>
  );
}
