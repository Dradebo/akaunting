import React, { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import { useAuth } from '../contexts/AuthContext'
import { SyncService } from '../services/sync'
import * as api from '../api/openapiClient'
import { Layout } from './Layout'

interface Transaction {
  id: number
  type: 'income' | 'expense'
  amount: number
  paid_at: string
  notes?: string
  category_name?: string
}

export function TransactionList() {
  const [transactions, setTransactions] = useState<Transaction[]>([])
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [queueSize, setQueueSize] = useState(0)
  const [deviceId] = useState(() => localStorage.getItem('device_id') || crypto.randomUUID())
  
  const { token, user } = useAuth()
  const syncService = new SyncService(deviceId)

  const loadTransactions = async () => {
    if (!token) return
    
    setIsLoading(true)
    try {
      const response = await api.listTransactions(token)
      setTransactions(response.data || [])
      setError(null)
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to load transactions')
    } finally {
      setIsLoading(false)
    }
  }

  const loadQueueSize = async () => {
    const count = await syncService.countQueued()
    setQueueSize(count)
  }

  const handleSync = async () => {
    if (!token) return
    
    try {
      await syncService.syncQueued(token)
      await loadQueueSize()
      await loadTransactions() // Reload to show synced data
    } catch (err: any) {
      setError('Sync failed: ' + (err.message || 'Unknown error'))
    }
  }

  useEffect(() => {
    localStorage.setItem('device_id', deviceId)
    loadTransactions()
    loadQueueSize()
  }, [token])

  const formatAmount = (amount: number, type: string) => {
    const formatted = (amount / 100).toFixed(2) // Assuming amount is in minor units
    return type === 'income' ? `+$${formatted}` : `-$${formatted}`
  }

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString()
  }

  const calculateTotals = () => {
    const income = transactions
      .filter(t => t.type === 'income')
      .reduce((sum, t) => sum + t.amount, 0) / 100

    const expense = transactions
      .filter(t => t.type === 'expense')  
      .reduce((sum, t) => sum + t.amount, 0) / 100

    const balance = income - expense

    return { income, expense, balance }
  }

  const { income, expense, balance } = calculateTotals()

  return (
    <Layout title="Transactions" showLogout={true}>
      <div className="transaction-list">
        <div className="actions-bar">
          <div className="sync-status">
            <button 
              onClick={handleSync}
              className="btn btn-secondary"
              disabled={queueSize === 0}
            >
              Sync ({queueSize})
            </button>
          </div>
          <div className="action-buttons">
            <Link to="/analytics" className="btn btn-secondary">
              Analytics
            </Link>
            <Link to="/transactions/create" className="btn btn-primary">
              New Transaction
            </Link>
          </div>
        </div>

        <div className="totals-panel">
          <div className="total-item income">
            <span className="total-label">Income</span>
            <span className="total-amount">+${income.toFixed(2)}</span>
          </div>
          <div className="total-item expense">
            <span className="total-label">Expense</span>
            <span className="total-amount">-${expense.toFixed(2)}</span>
          </div>
          <div className="total-item balance">
            <span className="total-label">Balance</span>
            <span className={`total-amount ${balance >= 0 ? 'positive' : 'negative'}`}>
              ${balance.toFixed(2)}
            </span>
          </div>
        </div>

        {error && (
          <div className="alert alert-danger">
            {error}
          </div>
        )}

        {isLoading ? (
          <div className="loading">Loading transactions...</div>
        ) : transactions.length === 0 ? (
          <div className="empty-state">
            <p>No transactions yet.</p>
            <Link to="/transactions/create" className="btn btn-primary">
              Create your first transaction
            </Link>
          </div>
        ) : (
          <div className="transaction-items">
            {transactions.map((transaction) => (
              <div key={transaction.id} className={`transaction-item ${transaction.type}`}>
                <div className="transaction-info">
                  <div className="transaction-description">
                    {transaction.notes || 'No description'}
                  </div>
                  <div className="transaction-category">
                    {transaction.category_name || 'Uncategorized'}
                  </div>
                  <div className="transaction-date">
                    {formatDate(transaction.paid_at)}
                  </div>
                </div>
                <div className={`transaction-amount ${transaction.type}`}>
                  {formatAmount(transaction.amount, transaction.type)}
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </Layout>
  )
}