import { useState } from "react";

export default function useFormErrors() {
  const [errors, setErrors] = useState({});

  const handleErrors = (err) => {
    if (err.response?.data?.errors) {
      setErrors(err.response.data.errors);
    } else if (err.response?.data?.message) {
      setErrors({ general: [err.response.data.message] });
    }
  };

  const clearErrors = () => setErrors({});

  const getError = (field) => {
    if (errors[field]) {
      return errors[field][0];
    }
    return null;
  };

  const addError = (key, value) => {
    setErrors((prev) => ({ ...prev, [key]: [value] })); // ✅ Computed property!
  };

  return { errors, addError, handleErrors, clearErrors, getError };
}
