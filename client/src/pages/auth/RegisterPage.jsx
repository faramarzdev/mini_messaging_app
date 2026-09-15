import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import AuthInput from "../../components/ui/AuthInput";
import { useAuth } from "../../context/AuthContext";
import LoadingOverlay from "../../components/ui/LoadingOverlay";
import {
  MailIcon,
  LockIcon,
  ConfirmPasswordIcon,
  UserIcon,
} from "../../components/icons/AuthIcons";
import { isValidEmail, isValidPassword } from "../../utils/validation";
import { getErrorMessage } from "../../utils/getErrorMessage";

export default function RegisterPage() {
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [termsAccepted, setTermsAccepted] = useState(false);
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState([]);
  const navigate = useNavigate();
  const { register } = useAuth();

  const handleSubmission = async (e) => {
    e.preventDefault();
    setErrors([]);
    setLoading(true);

    const validationErrors = [];

    if (!name || !email || !password || !confirmPassword) {
      validationErrors.push("Please fill in all fields");
    }
    if (!isValidEmail(email)) {
      validationErrors.push("Please enter a valid email address");
    }
    if (password !== confirmPassword) {
      validationErrors.push("Passwords do not match");
    }
    if (!isValidPassword(password)) {
      validationErrors.push("Password must be at least 8 characters");
    }
    if (!termsAccepted) {
      validationErrors.push("You must agree to the Terms of Service");
    }

    if (validationErrors.length > 0) {
      setErrors(validationErrors);
      setLoading(false);
      return;
    }

    try {
      await register(name, email, password, confirmPassword);
      navigate("/app"); // registration would log user in too
    } catch (err) {
      setErrors([getErrorMessage(err)]);
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
          Welcome
        </h1>
        <p
          id="form-subtitle"
          className="text-sm text-gray-500 dark:text-gray-400 mt-1"
        >
          Register an account
        </p>
      </div>

      <div id="panel-register" className="tab-panel space-y-5">
        <form
          id="register-form"
          className="space-y-4"
          onSubmit={handleSubmission}
        >
          {loading && (
            <LoadingOverlay message="Registering an account for you!" />
          )}

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
            label="Full Name"
            type="text"
            id="name"
            value={name}
            onChange={(e) => setName(e.target.value)}
            placeholder="John Doe"
            iconSvg={<UserIcon />}
          />

          <AuthInput
            label="Email address"
            type="email"
            id="email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            placeholder="you@example.com"
            iconSvg={<MailIcon />}
          />

          <AuthInput
            label="Password"
            type="password"
            id="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            placeholder="••••••••"
            iconSvg={<LockIcon />}
            hint="Must be at least 8 characters"
          />

          <AuthInput
            label="Confirm Password"
            type="password"
            id="confirm-password"
            value={confirmPassword}
            onChange={(e) => setConfirmPassword(e.target.value)}
            placeholder="••••••••"
            iconSvg={<ConfirmPasswordIcon />}
          />

          <div className="flex items-start gap-2.5">
            <input
              type="checkbox"
              id="terms"
              checked={termsAccepted}
              onChange={(e) => setTermsAccepted(e.target.checked)}
              className="mt-1 w-4 h-4 rounded border-stone-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500/20 focus:ring-2 transition"
            />
            <label
              htmlFor="terms"
              className="text-sm text-gray-600 dark:text-gray-400"
            >
              I agree to the{" "}
              <a
                href="#"
                className="text-indigo-600 dark:text-indigo-400 hover:underline font-medium"
              >
                Terms of Service
              </a>{" "}
              and{" "}
              <a
                href="#"
                className="text-indigo-600 dark:text-indigo-400 hover:underline font-medium"
              >
                Privacy Policy
              </a>
            </label>
          </div>

          <button
            type="submit"
            className="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-semibold rounded-xl shadow-sm shadow-indigo-200/50 dark:shadow-indigo-900/30 transition duration-200 flex items-center justify-center gap-2"
          >
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
                d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"
              />
            </svg>
            <span>Create Account</span>
          </button>
        </form>

        <p className="text-center text-sm text-gray-500 dark:text-gray-400 mt-5">
          Already have an account?
          <Link
            to="/auth/login"
            id="switch-to-login"
            className="text-indigo-600 dark:text-indigo-400 font-medium hover:underline ml-1"
          >
            Sign in
          </Link>
        </p>
      </div>
    </div>
  );
}
