import React, { useState } from 'react'
import { useNavigate, Link } from 'react-router-dom'
import { useAuth } from '../contexts/AuthContext'
import * as api from '../api/openapiClient'
import { Layout } from './Layout'

export function Register() {
  const [phone, setPhone] = useState('')
  const [name, setName] = useState('')
  const [pin, setPin] = useState('')
  const [confirmPin, setConfirmPin] = useState('')
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  
  const { login } = useAuth()
  const navigate = useNavigate()

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setIsLoading(true)
    setError(null)

    if (pin !== confirmPin) {
      setError('PINs do not match')
      setIsLoading(false)
      return
    }

    if (pin.length < 4) {
      setError('PIN must be at least 4 characters')
      setIsLoading(false)
      return
    }

    try {
      const response = await api.register({ phone, name: name || null, pin })
      login(response.token, response.user)
      navigate('/transactions')
    } catch (err: any) {
      setError(err.response?.data?.message || 'Registration failed')
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <Layout title="Akaunting Mobile - Register">
      <div className="auth-form">
        <form onSubmit={handleSubmit}>
          <div className="form-group">
            <label htmlFor="phone">Phone Number *</label>
            <input
              id="phone"
              type="tel"
              value={phone}
              onChange={(e) => setPhone(e.target.value)}
              placeholder="Enter your phone number"
              required
              disabled={isLoading}
              className="form-control"
            />
          </div>

          <div className="form-group">
            <label htmlFor="name">Name</label>
            <input
              id="name"
              type="text"
              value={name}
              onChange={(e) => setName(e.target.value)}
              placeholder="Enter your name (optional)"
              disabled={isLoading}
              className="form-control"
            />
          </div>

          <div className="form-group">
            <label htmlFor="pin">PIN *</label>
            <input
              id="pin"
              type="password"
              value={pin}
              onChange={(e) => setPin(e.target.value)}
              placeholder="Create a 4-digit PIN"
              required
              minLength={4}
              disabled={isLoading}
              className="form-control"
            />
          </div>

          <div className="form-group">
            <label htmlFor="confirmPin">Confirm PIN *</label>
            <input
              id="confirmPin"
              type="password"
              value={confirmPin}
              onChange={(e) => setConfirmPin(e.target.value)}
              placeholder="Confirm your PIN"
              required
              minLength={4}
              disabled={isLoading}
              className="form-control"
            />
          </div>

          {error && (
            <div className="alert alert-danger">
              {error}
            </div>
          )}

          <div className="form-actions">
            <button 
              type="submit" 
              className="btn btn-primary"
              disabled={isLoading}
            >
              {isLoading ? 'Creating Account...' : 'Register'}
            </button>
          </div>

          <div className="auth-links">
            <Link to="/login">Already have an account? Login</Link>
          </div>
        </form>
      </div>
    </Layout>
  )
}