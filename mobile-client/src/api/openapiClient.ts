import axios from 'axios'
import type { components } from './openapiTypes'

const API_BASE = (import.meta.env.VITE_API_BASE as string) || '/api'
const api = axios.create({ baseURL: API_BASE })

// Typed shortcuts from generated OpenAPI types
export type RegisterRequest = components['schemas']['RegisterRequest']
export type AuthResponse = components['schemas']['AuthResponse']

export async function register(req: RegisterRequest): Promise<AuthResponse> {
  const r = await api.post<components['schemas']['AuthResponse']>('/mobile/register', req)
  return r.data
}

export async function login(req: { phone: string; pin: string }): Promise<AuthResponse> {
  const r = await api.post<components['schemas']['AuthResponse']>('/mobile/login', req)
  return r.data
}

export type TransactionPayload = components['schemas']['TransactionPayload']
export type TransactionResponse = components['schemas']['TransactionResponse']

export interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  last_page: number
  total: number
}

export async function listTransactions(token?: string): Promise<PaginatedResponse<TransactionResponse>> {
  const headers: any = {}
  if (token) headers.Authorization = `Bearer ${token}`
  const r = await api.get<PaginatedResponse<TransactionResponse>>('/mobile/transactions', { headers })
  return r.data
}

export async function createTransaction(payload: TransactionPayload, token?: string): Promise<TransactionResponse> {
  const headers: any = {}
  if (token) headers.Authorization = `Bearer ${token}`
  const r = await api.post<TransactionResponse>('/mobile/transactions', payload, { headers })
  return r.data
}

export type SyncRecord = components['schemas']['SyncRecord']
export type SyncRequest = components['schemas']['SyncRequest']
export type SyncResponse = components['schemas']['SyncResponse']

export async function sync(req: SyncRequest, token?: string): Promise<SyncResponse> {
  const headers: any = { 'Content-Type': 'application/json' }
  if (token) headers.Authorization = `Bearer ${token}`
  const r = await api.post<SyncResponse>('/mobile/sync', req, { headers })
  return r.data
}

export default { register, login, listTransactions, createTransaction, sync }
