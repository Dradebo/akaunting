import { openDB, DBSchema } from 'idb'
import * as api from '../api/openapiClient'

interface MobileDB extends DBSchema {
  'outbox': {
    key: string
    value: {
      client_id: string
      op: 'create' | 'update' | 'delete'
      payload: any
      created_at: string
    }
  }
}

export class SyncService {
  dbPromise: Promise<any>
  deviceId: string

  constructor(deviceId: string) {
    this.deviceId = deviceId
    this.dbPromise = openDB<MobileDB>('akaunting-mobile', 1, {
      upgrade(db) {
        db.createObjectStore('outbox', { keyPath: 'client_id' })
      }
    })
  }

  async queueRecord(record: { client_id: string, op: string, payload: any }) {
    const db = await this.dbPromise
    await db.put('outbox', { ...record, created_at: new Date().toISOString() })
  }

  async getAllQueued() {
    const db = await this.dbPromise
    return await db.getAll('outbox')
  }

  async countQueued() {
    const db = await this.dbPromise
    return await db.count('outbox')
  }

  async clearQueued(clientIds: string[]) {
    const db = await this.dbPromise
    const tx = db.transaction('outbox', 'readwrite')
    for (const id of clientIds) {
      tx.store.delete(id)
    }
    await tx.done
  }

  async syncQueued(token?: string) {
    const records = await this.getAllQueued()
    if (!records.length) return { applied: [], conflicts: [] }

  const payload: api.SyncRequest = { device_id: this.deviceId, records }
  const data = await api.sync(payload, token)
    // remove applied records from outbox
    const appliedClientIds = (data.applied || []).map((a: any) => a.client_id)
    await this.clearQueued(appliedClientIds)
    return data
  }
}
