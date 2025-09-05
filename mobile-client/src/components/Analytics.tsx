import React, { useState, useEffect } from 'react'
import { useAuth } from '../contexts/AuthContext'
import { AnalyticsChart } from './AnalyticsChart'
import { Layout } from './Layout'
import * as api from '../api/openapiClient'

interface Transaction {
  id: number
  type: 'income' | 'expense'
  amount: number
  paid_at: string
  notes?: string
}

export function Analytics() {
  const [transactions, setTransactions] = useState<Transaction[]>([])
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  
  const { token } = useAuth()

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

  useEffect(() => {
    loadTransactions()
  }, [token])

  return (
    <Layout title="Analytics" showLogout={true}>
      <div className="analytics-page">
        {error && (
          <div className="alert alert-danger">
            {error}
          </div>
        )}

        {isLoading ? (
          <div className="loading">Loading analytics...</div>
        ) : transactions.length === 0 ? (
          <div className="empty-state">
            <p>No transaction data available for analysis.</p>
          </div>
        ) : (
          <AnalyticsChart transactions={transactions} />
        )}
      </div>
    </Layout>
  )
}