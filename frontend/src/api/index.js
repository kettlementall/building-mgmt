import http from './http'

// 認證
export const auth = {
  login: (data)  => http.post('/login', data),
  logout: ()     => http.post('/logout'),
  me: ()         => http.get('/me'),
}

// 戶別
export const units = {
  list: ()           => http.get('/units'),
  get: (id)          => http.get(`/units/${id}`),
  create: (data)     => http.post('/units', data),
  update: (id, data) => http.put(`/units/${id}`, data),
  remove: (id)       => http.delete(`/units/${id}`),
  bills: (id)        => http.get(`/units/${id}/bills`),
  residents: (id)    => http.get(`/units/${id}/residents`),
}

// 住戶
export const residents = {
  list: (params)     => http.get('/residents', { params }),
  get: (id)          => http.get(`/residents/${id}`),
  create: (data)     => http.post('/residents', data),
  update: (id, data) => http.put(`/residents/${id}`, data),
  remove: (id)       => http.delete(`/residents/${id}`),
}

// 管理費規則
export const feeRules = {
  list: ()           => http.get('/fee-rules'),
  get: (id)          => http.get(`/fee-rules/${id}`),
  create: (data)     => http.post('/fee-rules', data),
  update: (id, data) => http.put(`/fee-rules/${id}`, data),
  remove: (id)       => http.delete(`/fee-rules/${id}`),
}

// 帳單
export const bills = {
  list: (params)     => http.get('/bills', { params }),
  get: (id)          => http.get(`/bills/${id}`),
  generate: (data)   => http.post('/bills/generate', data),
  update: (id, data) => http.put(`/bills/${id}`, data),
  remove: (id)       => http.delete(`/bills/${id}`),
  sendNotice: (id)   => http.post(`/bills/${id}/send-notice`),
}

// 繳費
export const payments = {
  list: (params)   => http.get('/payments', { params }),
  get: (id)        => http.get(`/payments/${id}`),
  create: (data)   => http.post('/payments', data),
  remove: (id)     => http.delete(`/payments/${id}`),
  byBill: (billId) => http.get(`/bills/${billId}/payment`),
}

// 支出分類
export const expenseCategories = {
  list: ()           => http.get('/expense-categories'),
  create: (data)     => http.post('/expense-categories', data),
  update: (id, data) => http.put(`/expense-categories/${id}`, data),
  remove: (id)       => http.delete(`/expense-categories/${id}`),
}

// 支出明細
export const expenses = {
  list: (params)          => http.get('/expenses', { params }),
  get: (id)               => http.get(`/expenses/${id}`),
  create: (data)          => http.post('/expenses', data),
  update: (id, data)      => http.put(`/expenses/${id}`, data),
  remove: (id)            => http.delete(`/expenses/${id}`),
  uploadAttachment: (id, formData) =>
    http.post(`/expenses/${id}/attachments`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    }),
  deleteAttachment: (expenseId, attachmentId) =>
    http.delete(`/expenses/${expenseId}/attachments/${attachmentId}`),
}

// 報表
export const reports = {
  income:  (params) => http.get('/reports/income',  { params }),
  expense: (params) => http.get('/reports/expense', { params }),
  balance: (params) => http.get('/reports/balance', { params }),
  overdue: (params) => http.get('/reports/overdue', { params }),
}
