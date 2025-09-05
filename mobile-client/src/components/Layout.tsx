import React, { ReactNode } from 'react'
import { useAuth } from '../contexts/AuthContext'

interface LayoutProps {
  children: ReactNode
  title: string
  showLogout?: boolean
}

export function Layout({ children, title, showLogout = false }: LayoutProps) {
  const { logout, user } = useAuth()

  return (
    <div className="app">
      <header className="app-header">
        <div className="header-content">
          <h1>{title}</h1>
          {showLogout && user && (
            <div className="header-actions">
              <span className="user-name">Hi, {user.name || 'User'}</span>
              <button onClick={logout} className="btn-secondary">
                Logout
              </button>
            </div>
          )}
        </div>
      </header>
      <main className="app-main">
        {children}
      </main>
    </div>
  )
}