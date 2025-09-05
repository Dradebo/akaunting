import React from 'react'
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom'
import { AuthProvider, useAuth } from './contexts/AuthContext'
import { Login } from './components/Login'
import { Register } from './components/Register'
import { TransactionList } from './components/TransactionList'
import { TransactionForm } from './components/TransactionForm'
import { Analytics } from './components/Analytics'
import { PWAInstallPrompt } from './components/PWAInstallPrompt'

function ProtectedRoute({ children }: { children: React.ReactNode }) {
  const { isAuthenticated, isLoading } = useAuth()
  
  if (isLoading) {
    return <div className="loading">Loading...</div>
  }
  
  return isAuthenticated ? <>{children}</> : <Navigate to="/login" />
}

function PublicRoute({ children }: { children: React.ReactNode }) {
  const { isAuthenticated, isLoading } = useAuth()
  
  if (isLoading) {
    return <div className="loading">Loading...</div>
  }
  
  return isAuthenticated ? <Navigate to="/transactions" /> : <>{children}</>
}

function AppRoutes() {
  return (
    <Router>
      <Routes>
        <Route path="/login" element={
          <PublicRoute>
            <Login />
          </PublicRoute>
        } />
        <Route path="/register" element={
          <PublicRoute>
            <Register />
          </PublicRoute>
        } />
        <Route path="/transactions" element={
          <ProtectedRoute>
            <TransactionList />
          </ProtectedRoute>
        } />
        <Route path="/transactions/create" element={
          <ProtectedRoute>
            <TransactionForm />
          </ProtectedRoute>
        } />
        <Route path="/analytics" element={
          <ProtectedRoute>
            <Analytics />
          </ProtectedRoute>
        } />
        <Route path="/" element={<Navigate to="/transactions" />} />
      </Routes>
    </Router>
  )
}

export default function App() {
  return (
    <AuthProvider>
      <AppRoutes />
      <PWAInstallPrompt />
    </AuthProvider>
  )
}
