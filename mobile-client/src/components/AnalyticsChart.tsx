import React, { useState, useMemo } from 'react'
import { 
  BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, 
  LineChart, Line 
} from 'recharts'
import { 
  format, startOfWeek, endOfWeek, startOfMonth, endOfMonth,
  eachDayOfInterval, eachWeekOfInterval, isWithinInterval,
  parseISO, addDays, startOfDay
} from 'date-fns'

interface Transaction {
  id: number
  type: 'income' | 'expense'
  amount: number
  paid_at: string
  notes?: string
}

interface AnalyticsChartProps {
  transactions: Transaction[]
}

type ViewType = 'daily' | 'weekly' | 'monthly'
type ChartType = 'bar' | 'line'

export function AnalyticsChart({ transactions }: AnalyticsChartProps) {
  const [viewType, setViewType] = useState<ViewType>('weekly')
  const [chartType, setChartType] = useState<ChartType>('bar')
  const [startDate, setStartDate] = useState(() => {
    const today = new Date()
    return format(startOfWeek(today), 'yyyy-MM-dd')
  })
  const [endDate, setEndDate] = useState(() => {
    const today = new Date()
    return format(endOfWeek(today), 'yyyy-MM-dd')
  })
  const [showIncome, setShowIncome] = useState(true)
  const [showExpense, setShowExpense] = useState(true)

  const chartData = useMemo(() => {
    if (!transactions.length) return []

    const start = startOfDay(new Date(startDate))
    const end = startOfDay(addDays(new Date(endDate), 1)) // Include end date
    
    // Filter transactions within date range
    const filteredTransactions = transactions.filter(t => {
      const transactionDate = parseISO(t.paid_at)
      return isWithinInterval(transactionDate, { start, end })
    })

    let intervals: Date[] = []
    let formatPattern = ''
    let groupKey = ''

    // Generate intervals based on view type
    switch (viewType) {
      case 'daily':
        intervals = eachDayOfInterval({ start, end })
        formatPattern = 'MMM dd'
        groupKey = 'yyyy-MM-dd'
        break
      case 'weekly':
        intervals = eachWeekOfInterval({ start, end }, { weekStartsOn: 1 }) // Start on Monday
        formatPattern = 'MMM dd'
        groupKey = 'yyyy-ww'
        break
      case 'monthly':
        // For monthly, we'll group by month
        const monthStart = startOfMonth(start)
        const monthEnd = endOfMonth(end)
        intervals = eachWeekOfInterval({ start: monthStart, end: monthEnd }, { weekStartsOn: 1 })
        formatPattern = 'MMM dd'
        groupKey = 'yyyy-MM'
        break
    }

    // Group transactions by interval
    return intervals.map(intervalDate => {
      let intervalStart: Date
      let intervalEnd: Date
      let label: string

      switch (viewType) {
        case 'daily':
          intervalStart = intervalDate
          intervalEnd = addDays(intervalDate, 1)
          label = format(intervalDate, formatPattern)
          break
        case 'weekly':
          intervalStart = startOfWeek(intervalDate, { weekStartsOn: 1 })
          intervalEnd = endOfWeek(intervalDate, { weekStartsOn: 1 })
          label = `${format(intervalStart, 'MMM dd')} - ${format(intervalEnd, 'dd')}`
          break
        case 'monthly':
          intervalStart = startOfWeek(intervalDate, { weekStartsOn: 1 })
          intervalEnd = endOfWeek(intervalDate, { weekStartsOn: 1 })
          label = `Week ${format(intervalDate, 'MMM dd')}`
          break
      }

      const periodTransactions = filteredTransactions.filter(t => {
        const tDate = parseISO(t.paid_at)
        return isWithinInterval(tDate, { start: intervalStart, end: intervalEnd })
      })

      const income = periodTransactions
        .filter(t => t.type === 'income')
        .reduce((sum, t) => sum + t.amount, 0) / 100

      const expense = periodTransactions
        .filter(t => t.type === 'expense')
        .reduce((sum, t) => sum + t.amount, 0) / 100

      return {
        period: label,
        income: income,
        expense: expense,
        net: income - expense
      }
    }).filter(item => item.income > 0 || item.expense > 0 || viewType === 'daily') // Show all days for daily view
  }, [transactions, viewType, startDate, endDate])

  const handlePresetRange = (preset: string) => {
    const today = new Date()
    let start: Date, end: Date

    switch (preset) {
      case 'thisWeek':
        start = startOfWeek(today, { weekStartsOn: 1 })
        end = endOfWeek(today, { weekStartsOn: 1 })
        setViewType('daily')
        break
      case 'lastWeek':
        const lastWeek = addDays(today, -7)
        start = startOfWeek(lastWeek, { weekStartsOn: 1 })
        end = endOfWeek(lastWeek, { weekStartsOn: 1 })
        setViewType('daily')
        break
      case 'thisMonth':
        start = startOfMonth(today)
        end = endOfMonth(today)
        setViewType('weekly')
        break
      case 'lastMonth':
        const lastMonth = addDays(startOfMonth(today), -1)
        start = startOfMonth(lastMonth)
        end = endOfMonth(lastMonth)
        setViewType('weekly')
        break
      default:
        return
    }

    setStartDate(format(start, 'yyyy-MM-dd'))
    setEndDate(format(end, 'yyyy-MM-dd'))
  }

  const totalIncome = chartData.reduce((sum, item) => sum + item.income, 0)
  const totalExpense = chartData.reduce((sum, item) => sum + item.expense, 0)
  const netTotal = totalIncome - totalExpense

  const ChartComponent = chartType === 'bar' ? BarChart : LineChart

  return (
    <div className="analytics-chart">
      <div className="chart-header">
        <h3>Spending Analysis</h3>
        
        {/* Quick preset buttons */}
        <div className="chart-presets">
          <button onClick={() => handlePresetRange('thisWeek')} className="btn-preset">
            This Week
          </button>
          <button onClick={() => handlePresetRange('lastWeek')} className="btn-preset">
            Last Week
          </button>
          <button onClick={() => handlePresetRange('thisMonth')} className="btn-preset">
            This Month
          </button>
          <button onClick={() => handlePresetRange('lastMonth')} className="btn-preset">
            Last Month
          </button>
        </div>
      </div>

      {/* Date range and view controls */}
      <div className="chart-controls">
        <div className="date-controls">
          <label>
            From: 
            <input 
              type="date" 
              value={startDate} 
              onChange={e => setStartDate(e.target.value)}
              className="date-input"
            />
          </label>
          <label>
            To: 
            <input 
              type="date" 
              value={endDate} 
              onChange={e => setEndDate(e.target.value)}
              className="date-input"
            />
          </label>
        </div>

        <div className="view-controls">
          <label>
            View:
            <select value={viewType} onChange={e => setViewType(e.target.value as ViewType)}>
              <option value="daily">Daily</option>
              <option value="weekly">Weekly</option>
              <option value="monthly">Monthly</option>
            </select>
          </label>
          
          <label>
            Chart:
            <select value={chartType} onChange={e => setChartType(e.target.value as ChartType)}>
              <option value="bar">Bar</option>
              <option value="line">Line</option>
            </select>
          </label>
        </div>

        <div className="toggle-controls">
          <label className="checkbox-label">
            <input 
              type="checkbox" 
              checked={showIncome} 
              onChange={e => setShowIncome(e.target.checked)} 
            />
            Income
          </label>
          <label className="checkbox-label">
            <input 
              type="checkbox" 
              checked={showExpense} 
              onChange={e => setShowExpense(e.target.checked)} 
            />
            Expense
          </label>
        </div>
      </div>

      {/* Summary totals */}
      <div className="chart-summary">
        <div className="summary-item income">
          <span>Total Income: ${totalIncome.toFixed(2)}</span>
        </div>
        <div className="summary-item expense">
          <span>Total Expense: ${totalExpense.toFixed(2)}</span>
        </div>
        <div className={`summary-item net ${netTotal >= 0 ? 'positive' : 'negative'}`}>
          <span>Net: ${netTotal.toFixed(2)}</span>
        </div>
      </div>

      {/* Chart */}
      <div className="chart-container">
        <ResponsiveContainer width="100%" height={300}>
          <ChartComponent data={chartData}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis 
              dataKey="period" 
              angle={-45}
              textAnchor="end"
              height={80}
              fontSize={12}
            />
            <YAxis />
            <Tooltip 
              formatter={(value, name) => [`$${Number(value).toFixed(2)}`, name]}
              labelStyle={{ color: '#374151' }}
            />
            
            {chartType === 'bar' ? (
              <>
                {showIncome && <Bar dataKey="income" fill="#16a34a" name="Income" />}
                {showExpense && <Bar dataKey="expense" fill="#dc2626" name="Expense" />}
              </>
            ) : (
              <>
                {showIncome && <Line type="monotone" dataKey="income" stroke="#16a34a" strokeWidth={2} name="Income" />}
                {showExpense && <Line type="monotone" dataKey="expense" stroke="#dc2626" strokeWidth={2} name="Expense" />}
              </>
            )}
          </ChartComponent>
        </ResponsiveContainer>
      </div>
    </div>
  )
}