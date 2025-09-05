import React, { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useAuth } from '../contexts/AuthContext'
import { SyncService } from '../services/sync'
import * as api from '../api/openapiClient'
import { Layout } from './Layout'

export function TransactionForm() {
  const [type, setType] = useState<'income' | 'expense'>('expense')
  const [amount, setAmount] = useState('')
  const [description, setDescription] = useState('')
  const [categoryName, setCategoryName] = useState('')
  const [date, setDate] = useState(new Date().toISOString().split('T')[0])
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [deviceId] = useState(() => localStorage.getItem('device_id') || crypto.randomUUID())
  
  const { token } = useAuth()
  const navigate = useNavigate()
  const syncService = new SyncService(deviceId)

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setIsLoading(true)
    setError(null)

    if (!amount || parseFloat(amount) <= 0) {
      setError('Please enter a valid amount')
      setIsLoading(false)
      return
    }

    const clientId = crypto.randomUUID()
    const amountMinor = Math.round(parseFloat(amount) * 100) // Convert to minor units

    const payload = {
      client_id: clientId,
      type,
      amount_minor: amountMinor,
      date,
      category_name: categoryName || null,
      notes: description || null
    }

    try {
      if (navigator.onLine && token) {
        // Try to sync immediately when online
        await api.createTransaction(payload, token)
        navigate('/transactions')
      } else {
        // Queue for offline sync
        await syncService.queueRecord({
          client_id: clientId,
          op: 'create',
          payload
        })
        navigate('/transactions')
      }
    } catch (err: any) {
      // If online sync fails, fallback to offline queue
      try {
        await syncService.queueRecord({
          client_id: clientId,
          op: 'create', 
          payload
        })
        navigate('/transactions')
      } catch (queueErr: any) {
        setError('Failed to save transaction: ' + (err.message || 'Unknown error'))
      }
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <Layout title="New Transaction" showLogout={true}>
      <div className="transaction-form">
        <form onSubmit={handleSubmit}>
          <div className="form-group">
            <label>Transaction Type</label>
            <div className="radio-group">
              <label>
                <input
                  type="radio"
                  value="income"
                  checked={type === 'income'}
                  onChange={(e) => setType(e.target.value as 'income' | 'expense')}
                  disabled={isLoading}
                />
                Income
              </label>
              <label>
                <input
                  type="radio"
                  value="expense"
                  checked={type === 'expense'}
                  onChange={(e) => setType(e.target.value as 'income' | 'expense')}
                  disabled={isLoading}
                />
                Expense
              </label>
            </div>
          </div>

          <div className="form-group">
            <label htmlFor="amount">Amount *</label>
            <input
              id="amount"
              type="number"
              step="0.01"
              min="0"
              value={amount}
              onChange={(e) => setAmount(e.target.value)}
              placeholder="0.00"
              required
              disabled={isLoading}
              className="form-control"
            />
          </div>

          <div className="form-group">
            <label htmlFor="date">Date</label>
            <input
              id="date"
              type="date"
              value={date}
              onChange={(e) => setDate(e.target.value)}
              disabled={isLoading}
              className="form-control"
            />
          </div>

          <div className="form-group">
            <label htmlFor="categoryName">Category</label>
            <input
              id="categoryName"
              type="text"
              value={categoryName}
              onChange={(e) => setCategoryName(e.target.value)}
              placeholder="Enter category (optional)"
              disabled={isLoading}
              className="form-control"
            />
          </div>

          <div className="form-group">
            <label htmlFor="description">Description</label>
            <textarea
              id="description"
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              placeholder="Enter description (optional)"
              disabled={isLoading}
              className="form-control"
              rows={3}
            />
          </div>

          {error && (
            <div className="alert alert-danger">
              {error}
            </div>
          )}

          <div className="form-actions">
            <button 
              type="button"
              onClick={() => navigate('/transactions')}
              className="btn btn-secondary"
              disabled={isLoading}
            >
              Cancel
            </button>
            <button 
              type="submit" 
              className="btn btn-primary"
              disabled={isLoading}
            >
              {isLoading ? 'Saving...' : 'Save Transaction'}
            </button>
          </div>

          <div className="offline-notice">
            {!navigator.onLine && (
              <div className="alert alert-info">
                You're offline. Transaction will be saved and synced when online.
              </div>
            )}
          </div>
        </form>
      </div>
    </Layout>
  )
}