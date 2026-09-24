import { useQuery } from "@tanstack/react-query";
import { getProfile } from "../api/profile";

export default function useProfile(profileHandle) {
  const query = useQuery({
    queryKey: ["profile", profileHandle],
    queryFn: () => getProfile(profileHandle),
  });

  return {
    profile: query.data ?? null,
    meta: query.data?.meta,

    isLoading: query.isLoading,
    isFetching: query.isFetching,
    isError: query.isError,
    error: query.error,
    refetch: query.refetch,
  };
}
