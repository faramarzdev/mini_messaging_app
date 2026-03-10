import { useState } from "react";
import { useNavigate, Link } from "react-router-dom";
import { useAuth } from "../context/AuthContext";
import useFormErrors from "../hooks/useFormErrors";
import FormInput from "../components/FormInput";
import Button from "../components/Button";
import AuthBox from "../components/AuthBox";
import LoadingSpinner from "../components/LoadingSpinner";

function Register() {
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [loading, setLoading] = useState(false);
  const { addError, handleErrors, clearErrors, getError } = useFormErrors();

  const { register } = useAuth();
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    clearErrors();
    if (password !== passwordConfirmation) {
      addError("password_confirmation", "Passwords do not match");
      return;
    }

    setLoading(true);
    try {
      await register({
        name: name,
        email: email,
        password: password,
        password_confirmation: passwordConfirmation,
      });
      navigate("/");
    } catch (err) {
      handleErrors(err);
    } finally {
      setLoading(false);
    }
  };

  return (
    <AuthBox title="Register">
      {getError("general") && (
        <div className="text-red-600 mb-10 font-extrabold">
          {getError("general")}
        </div>
      )}

      <form onSubmit={handleSubmit}>
        <FormInput
          label="Name"
          type="text"
          value={name}
          onChange={(e) => setName(e.target.value)}
          error={getError("name")}
          required
        />

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
        <FormInput
          label="Password Confimation"
          type="password"
          value={passwordConfirmation}
          onChange={(e) => setPasswordConfirmation(e.target.value)}
          error={getError("password_confirmation")}
          required
        />

        <Button
          type="submit"
          disabled={loading}
          children={
            loading ? (
              <>
                <LoadingSpinner />
                <span> Registering ...</span>
              </>
            ) : (
              "Register"
            )
          }
          className="w-full mt-5"
        />
      </form>

      <p className="mt-4">
        Already have an account?{" "}
        <Link to="/login" className="text-amber-600 hover:text-amber-800">
          Login
        </Link>
      </p>
    </AuthBox>
  );
}

export default Register;
