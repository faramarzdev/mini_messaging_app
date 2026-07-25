import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import AuthInput from "../../components/ui/AuthInput";
import { useAuth } from "../../context/AuthContext";
import LoadingOverlay from "../../components/ui/LoadingOverlay";

export default function LoginPage() {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState([]);
  const { login } = useAuth();
  const navigate = useNavigate();

  const handleSubmission = async (e) => {
    e.preventDefault();
    setLoading(true);
    setErrors([]);

    if (!email || !password) {
      setErrors(["Please fill in all fields"]);
      setLoading(false);
      return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      setErrors(["Please enter a valid email address"]);
      setLoading(false);
      return;
    }
    try {
      await login(email, password);
      navigate("/app");
    } catch (err) {
      if (err.response?.message) {
        setErrors((prev) => [...prev, err.response.message]);
      } else if (err.response?.data?.message) {
        setErrors((prev) => [...prev, err.response.data.message]);
      } else {
        setErrors((prev) => [
          ...prev,
          "Something went wrong. Please try again.",
        ]);
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <>
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
            Welcome Back
          </h1>
          <p
            id="form-subtitle"
            className="text-sm text-gray-500 dark:text-gray-400 mt-1"
          >
            Sign in to your account
          </p>
        </div>

        <div id="panel-login" className="tab-panel space-y-5">
          <form
            id="login-form"
            className="space-y-4"
            onSubmit={handleSubmission}
          >
            {loading && <LoadingOverlay message="Signing you in!" />}

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
              label="Email address"
              type="email"
              id="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="you@example.com"
              iconSvg={MailIcon()}
            />
            <AuthInput
              label="Password"
              type="password"
              id="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="••••••••"
              iconSvg={LockIcon()}
            />

            <button
              type="submit"
              className="cursor-pointer w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-semibold rounded-xl shadow-sm shadow-indigo-200/50 dark:shadow-indigo-900/30 transition duration-200 flex items-center justify-center gap-2"
            >
              <span>Sign In</span>
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
                  d="M13 7l5 5m0 0l-5 5m5-5H6"
                />
              </svg>
            </button>
          </form>

          <p className="text-center text-sm text-gray-500 dark:text-gray-400 mt-5">
            Don't have an account?
            <Link
              to="/auth/register"
              className="text-indigo-600 dark:text-indigo-400 font-medium hover:underline ml-1"
            >
              Sign up
            </Link>
          </p>
          <p className="text-center text-sm  mt-5 text-indigo-600 dark:text-indigo-400 font-medium hover:underline ml-1">
            <Link to="/auth/forgot-password">Forgot password?</Link>
          </p>
        </div>
      </div>
    </>
  );
}

// ─── Inline SVG icons ─────────────────────────────────────────────────────────

function MailIcon() {
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
        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
      />
    </svg>
  );
}

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
