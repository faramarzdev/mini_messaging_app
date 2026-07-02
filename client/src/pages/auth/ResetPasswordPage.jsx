import { useState } from "react";
import { Link, useSearchParams } from "react-router-dom";
import AuthInput from "../../components/ui/AuthInput";
import LoadingOverlay from "../../components/ui/LoadingOverlay";
import { useAuth } from "../../context/AuthContext";

export default function ResetPasswordPage() {
  const [password, setPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState([]);
  const [successMessage, setSuccessMessage] = useState(false);
  const { resetPassword } = useAuth();

  const [searchParams] = useSearchParams();
  const token = searchParams.get("token");
  const email = searchParams.get("email");
  const isRequestInvalid =
    !token || !email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  if (isRequestInvalid) {
    return (
      <div className="text-center font-bold text-red-600 dark:text-red-400 p-3 bg-red-100 dark:bg-red-900/30 rounded-lg">
        Invalid Request. Please follow the link sent to your email.
      </div>
    );
  }

  const handleSubmission = async (e) => {
    e.preventDefault();
    setLoading(true);
    setErrors([]);

    const validationErrors = [];
    if (!password || password !== confirmPassword) {
      validationErrors.push("Please fill both password fields equally.");
    }
    if (password.length < 8) {
      validationErrors.push("Password must be have at least 8 charcters.");
    }

    if (validationErrors.length > 0) {
      setErrors(validationErrors);
      setLoading(false);
      return;
    }

    try {
      await resetPassword(token, email, password, confirmPassword);
      setSuccessMessage(true);
    } catch (err) {
      const message =
        err.response?.data?.message ||
        err.response?.message ||
        "Something went wrong. Please try again.";
      setErrors([message]);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div id="auth-container">
      <div className="text-center mb-7">
        <div className="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 mb-3">
          <svg
            className="w-7 h-7"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
            />
          </svg>
        </div>
        <h1 id="form-title" className="text-2xl font-bold tracking-tight">
          Reset Password
        </h1>
        <p
          id="form-subtitle"
          className="text-sm text-gray-500 dark:text-gray-400 mt-1"
        >
          Set your new password below
        </p>
      </div>

      <div id="panel-login" className="tab-panel space-y-5">
        {loading && <LoadingOverlay message="Resetting your password!" />}
        {successMessage ? (
          <div className="text-center text-sm text-green-600 dark:text-green-400 mb-2 p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
            A password reset link has been sent to your email. you need to login
            with new password. <Link to="/auth/login">Login</Link>
          </div>
        ) : (
          <form
            id="login-form"
            className="space-y-4"
            onSubmit={handleSubmission}
          >
            <div id="errorBox">
              {errors.length > 0 && (
                <div className="text-center text-sm text-red-500 dark:text-red-400 mb-2">
                  {errors.map((error, index) => (
                    <p key={index}>{error}</p>
                  ))}
                </div>
              )}
            </div>

            <AuthInput
              label="New Password"
              type="password"
              id="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="••••••••"
              iconSvg={LockIcon()}
              hint="Must be at least 8 characters"
            />

            <AuthInput
              label="Rewrite Your New Password"
              type="password"
              id="confirm-password"
              value={confirmPassword}
              onChange={(e) => setConfirmPassword(e.target.value)}
              placeholder="••••••••"
              iconSvg={ConfirmPasswordIcon()}
            />

            <button
              type="submit"
              className="cursor-pointer w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-semibold rounded-xl shadow-sm shadow-indigo-200/50 dark:shadow-indigo-900/30 transition duration-200 flex items-center justify-center gap-2"
            >
              Set New Password
            </button>
          </form>
        )}
      </div>
    </div>
  );
}
// ─── Inline SVG icons ─────────────────────────────────────────────────────────

function LockIcon() {
  return (
    <svg
      className="w-4 h-4"
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
      viewBox="0 0 24 24"
    >
      <path
        strokeLinecap="round"
        strokeLinejoin="round"
        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
      />
    </svg>
  );
}
function ConfirmPasswordIcon() {
  return (
    <svg
      className="w-4 h-4"
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
      viewBox="0 0 24 24"
    >
      <path
        strokeLinecap="round"
        strokeLinejoin="round"
        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
      />
    </svg>
  );
}
