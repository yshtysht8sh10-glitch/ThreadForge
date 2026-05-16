import { createContext, ReactNode, useContext, useEffect, useMemo, useState } from 'react';
import { api } from './api';
import { UserProfile } from './types';

type AuthContextValue = {
  token: string;
  user: UserProfile | null;
  loading: boolean;
  login: (loginId: string, password: string) => Promise<void>;
  register: (payload: { login_id: string; password: string; display_name?: string; post_password?: string; home_url?: string; icon?: File | null }) => Promise<void>;
  updateProfile: (payload: { display_name: string; post_password: string; home_url?: string; icon?: File | null }) => Promise<void>;
  logout: () => Promise<void>;
};

const AuthContext = createContext<AuthContextValue | null>(null);
const TOKEN_KEY = 'threadforgeUserToken';

export function AuthProvider({ children }: { children: ReactNode }) {
  const [token, setToken] = useState(() => window.localStorage.getItem(TOKEN_KEY) ?? '');
  const [user, setUser] = useState<UserProfile | null>(null);
  const [loading, setLoading] = useState(Boolean(token));

  useEffect(() => {
    if (!token) {
      setLoading(false);
      setUser(null);
      return;
    }
    let ignore = false;
    setLoading(true);
    api.currentUser(token)
      .then((response) => {
        if (!ignore) setUser(response.user);
      })
      .catch(() => {
        if (!ignore) {
          setToken('');
          setUser(null);
          window.localStorage.removeItem(TOKEN_KEY);
        }
      })
      .finally(() => {
        if (!ignore) setLoading(false);
      });
    return () => {
      ignore = true;
    };
  }, [token]);

  const value = useMemo<AuthContextValue>(() => ({
    token,
    user,
    loading,
    login: async (loginId, password) => {
      const response = await api.loginUser(loginId, password);
      setToken(response.token);
      setUser(response.user);
      window.localStorage.setItem(TOKEN_KEY, response.token);
    },
    register: async (payload) => {
      const response = await api.registerUser(payload);
      setToken(response.token);
      setUser(response.user);
      window.localStorage.setItem(TOKEN_KEY, response.token);
    },
    updateProfile: async (payload) => {
      if (!token) throw new Error('Not logged in');
      const response = await api.updateUserProfile({ ...payload, token });
      setUser(response.user);
    },
    logout: async () => {
      if (token) {
        await api.logoutUser(token).catch(() => undefined);
      }
      setToken('');
      setUser(null);
      window.localStorage.removeItem(TOKEN_KEY);
    },
  }), [loading, token, user]);

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const value = useContext(AuthContext);
  if (!value) {
    return {
      token: '',
      user: null,
      loading: false,
      login: async () => undefined,
      register: async () => undefined,
      updateProfile: async () => undefined,
      logout: async () => undefined,
    };
  }
  return value;
}
