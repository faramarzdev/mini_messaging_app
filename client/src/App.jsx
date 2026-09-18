import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { ThemeProvider } from "./context/ThemeContext";
import { AuthProvider } from "./context/AuthContext";
import { createBrowserRouter, RouterProvider } from "react-router-dom";
import AppLayout from "./components/layouts/AppLayout";
import AuthLayout from "./components/layouts/AuthLayout";
import EmptyStatePage from "./components/layouts/EmptyStatePage";
import LoginPage from "./pages/auth/LoginPage";
import RegisterPage from "./pages/auth/RegisterPage";
import ForgotPasswordPage from "./pages/auth/ForgotPasswordPage";
import ResetPasswordPage from "./pages/auth/ResetPasswordPage";
import HomePage from "./pages/HomePage";
import { ROUTES } from "./routes/paths";

const queryClient = new QueryClient();

const router = createBrowserRouter([
  {
    path: ROUTES.home,
    element: <HomePage />,
  },
  {
    path: ROUTES.app,
    element: <AppLayout />,
    children: [
      { index: true, element: <EmptyStatePage /> },
      { path: ROUTES.appContacts, element: <div>Contacts page</div> },
      { path: ROUTES.appChat, element: <div>Chat page</div> },
      { path: ROUTES.appProfile, element: <div>Profile page</div> },
    ],
  },
  {
    path: ROUTES.auth,
    element: <AuthLayout />,
    children: [
      { path: ROUTES.authLogin, element: <LoginPage /> },
      { path: ROUTES.authRegister, element: <RegisterPage /> },
      { path: ROUTES.authForgotPassword, element: <ForgotPasswordPage /> },
      { path: ROUTES.authResetPassword, element: <ResetPasswordPage /> },
    ],
  },
]);

function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <AuthProvider>
        <ThemeProvider>
          <RouterProvider router={router} />
        </ThemeProvider>
      </AuthProvider>
    </QueryClientProvider>
  );
}

export default App;
