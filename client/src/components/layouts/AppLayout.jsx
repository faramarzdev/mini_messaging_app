import { Navigate, Outlet } from "react-router-dom";
import Sidebar from "../Sidebar";
import { useAuth } from "../../context/AuthContext";
import useConversations from "../../hooks/useConversations";
import LoadingOverlay from "../ui/LoadingOverlay";
import { ROUTES } from "../../routes/paths.js";

export default function AppLayout() {
  const { user, loading } = useAuth();
  const {
    conversations,
    isLoading: conversationsLoading,
    error,
  } = useConversations();

  if (loading) return <LoadingOverlay />;
  if (!user) return <Navigate to={ROUTES.authLogin} replace />;

  return (
    <div className="flex h-full w-full overflow-hidden">
      <Sidebar
        conversations={conversations}
        isConversationsLoading={conversationsLoading}
        conversationsErrors={error}
      />
      <main className="flex flex-1 min-w-0 overflow-hidden relative">
        <Outlet />
      </main>
    </div>
  );
}
