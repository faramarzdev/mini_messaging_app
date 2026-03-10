import { useState } from "react";
import { useNavigate, Link } from "react-router-dom";
import { useAuth } from "../context/AuthContext";
import useFormErrors from "../hooks/useFormErrors";
import FormInput from "../components/FormInput";
import Button from "../components/Button";
import AuthBox from "../components/AuthBox";
import LoadingSpinner from "../components/LoadingSpinner";

export default function Login() {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [loading, setLoading] = useState(false);

  const { errors, addError, handleErrors, clearErrors, getError } =
    useFormErrors();
  const { login } = useAuth();
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    clearErrors();
    setLoading(true);

    try {
      await login({ email, password });
      navigate("/");
    } catch (err) {
      handleErrors(err);
    } finally {
      setLoading(false);
    }
  };

  return (
    <AuthBox title="Login">
      {/* General errors (rate limit, server error) */}
      {getError("general") && (
        <div className="bg-red-50 text-red-600 text-sm p-3 rounded-lg mb-4">
          {getError("general")}
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-4">
        <FormInput
          label="Email"
          type="email"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          error={getError("email")}
          required
        />
        <FormInput
          label="Password"
          type="password"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          error={getError("password")}
          required
        />

        <Button
          type="submit"
          className="w-full"
          disabled={loading}
          children={
            loading ? (
              <>
                <LoadingSpinner />
                <span> Logging in ...</span>
              </>
            ) : (
              "Login"
            )
          }
        />
      </form>

      <p className="text-center text-sm text-gray-600 dark:text-gray-400 mt-6">
        Don't have an account?{" "}
        <Link
          to="/register"
          className="text-amber-600 hover:text-amber-800 font-medium"
        >
          Register here
        </Link>
      </p>
    </AuthBox>
  );
}
