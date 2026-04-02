<template>
  <el-card shadow="never">
    <template #header>
      <div class="card-header">
        <span>帳單管理</span>
        <div class="header-actions">
          <el-select v-model="filter.year"  placeholder="年份" style="width:100px" @change="fetchBills">
            <el-option v-for="y in yearOptions" :key="y" :label="`${y} 年`" :value="y" />
          </el-select>
          <el-select v-model="filter.month" placeholder="月份" clearable style="width:100px" @change="fetchBills">
            <el-option v-for="m in 12" :key="m" :label="`${m} 月`" :value="m" />
          </el-select>
          <el-select v-model="filter.status" placeholder="狀態" clearable style="width:110px" @change="fetchBills">
            <el-option label="未繳" value="unpaid" />
            <el-option label="已繳" value="paid" />
            <el-option label="逾期" value="overdue" />
          </el-select>
          <el-button type="success" @click="generateDialogVisible = true">批量產帳單</el-button>
        </div>
      </div>
    </template>

    <el-table :data="billList" v-loading="loading" stripe>
      <el-table-column prop="unit.floor"  label="樓層" width="80" />
      <el-table-column prop="unit.number" label="室號" width="80" />
      <el-table-column label="年月" width="100">
        <template #default="{ row }">{{ row.year }}/{{ String(row.month).padStart(2,'0') }}</template>
      </el-table-column>
      <el-table-column label="金額" width="120">
        <template #default="{ row }">NT$ {{ Number(row.amount).toLocaleString() }}</template>
      </el-table-column>
      <el-table-column prop="due_date" label="截止日" width="120" />
      <el-table-column label="狀態" width="90">
        <template #default="{ row }">
          <el-tag :type="statusType(row.status)" size="small">{{ statusLabel(row.status) }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="操作" width="200" fixed="right">
        <template #default="{ row }">
          <el-button size="small" @click="$router.push(`/bills/${row.id}`)">詳情</el-button>
          <el-button size="small" type="success" :disabled="row.status === 'paid'" @click="openPayDialog(row)">登錄收款</el-button>
          <el-button size="small" @click="sendNotice(row)" :disabled="row.status === 'paid'">通知</el-button>
        </template>
      </el-table-column>
    </el-table>

    <div class="pagination">
      <el-pagination
        v-model:current-page="page"
        :total="total"
        :page-size="20"
        layout="total, prev, pager, next"
        @current-change="fetchBills"
      />
    </div>
  </el-card>

  <!-- 批量產帳單 Dialog -->
  <el-dialog v-model="generateDialogVisible" title="批量產帳單" width="380px">
    <el-form :model="generateForm" label-width="90px">
      <el-form-item label="年份">
        <el-input-number v-model="generateForm.year" :min="2000" style="width:100%" />
      </el-form-item>
      <el-form-item label="月份">
        <el-select v-model="generateForm.month" style="width:100%">
          <el-option v-for="m in 12" :key="m" :label="`${m} 月`" :value="m" />
        </el-select>
      </el-form-item>
      <el-form-item label="截止日(日)">
        <el-input-number v-model="generateForm.due_days" :min="1" :max="28" style="width:100%" />
      </el-form-item>
    </el-form>
    <template #footer>
      <el-button @click="generateDialogVisible = false">取消</el-button>
      <el-button type="primary" :loading="generating" @click="handleGenerate">產生帳單</el-button>
    </template>
  </el-dialog>

  <!-- 登錄收款 Dialog -->
  <el-dialog v-model="payDialogVisible" title="登錄收款" width="380px">
    <el-form :model="payForm" :rules="payRules" ref="payFormRef" label-width="90px">
      <el-form-item label="金額" prop="amount">
        <el-input-number v-model="payForm.amount" :min="0" style="width:100%" />
      </el-form-item>
      <el-form-item label="繳費方式" prop="method">
        <el-select v-model="payForm.method" style="width:100%">
          <el-option label="現金" value="cash" />
          <el-option label="匯款" value="transfer" />
        </el-select>
      </el-form-item>
      <el-form-item label="繳費日期" prop="paid_at">
        <el-date-picker v-model="payForm.paid_at" type="date" value-format="YYYY-MM-DD" style="width:100%" />
      </el-form-item>
      <el-form-item label="匯款單號">
        <el-input v-model="payForm.reference" placeholder="選填" />
      </el-form-item>
    </el-form>
    <template #footer>
      <el-button @click="payDialogVisible = false">取消</el-button>
      <el-button type="primary" :loading="paying" @click="handlePay">確認收款</el-button>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { bills as billsApi, payments as paymentsApi } from '@/api'
import dayjs from 'dayjs'

const loading   = ref(false)
const billList  = ref([])
const total     = ref(0)
const page      = ref(1)

const filter = ref({ year: dayjs().year(), month: null, status: null })

const yearOptions = Array.from({ length: 5 }, (_, i) => dayjs().year() - i)

// 批量產帳單
const generateDialogVisible = ref(false)
const generating            = ref(false)
const generateForm          = ref({ year: dayjs().year(), month: dayjs().month() + 1, due_days: 15 })

// 登錄收款
const payDialogVisible = ref(false)
const paying           = ref(false)
const payFormRef       = ref(null)
const selectedBill     = ref(null)
const payForm          = ref({ amount: 0, method: 'cash', paid_at: dayjs().format('YYYY-MM-DD'), reference: '' })

const payRules = {
  amount:  [{ required: true, type: 'number', min: 1, message: '請輸入金額' }],
  method:  [{ required: true, message: '請選擇繳費方式' }],
  paid_at: [{ required: true, message: '請選擇繳費日期' }],
}

onMounted(fetchBills)

async function fetchBills() {
  loading.value = true
  const params = { page: page.value, ...filter.value }
  const res = await billsApi.list(params)
  billList.value = res.data
  total.value    = res.total
  loading.value  = false
}

async function handleGenerate() {
  generating.value = true
  try {
    const res = await billsApi.generate(generateForm.value)
    ElMessage.success(res.message)
    generateDialogVisible.value = false
    fetchBills()
  } finally {
    generating.value = false
  }
}

function openPayDialog(bill) {
  selectedBill.value = bill
  payForm.value = { amount: Number(bill.amount), method: 'cash', paid_at: dayjs().format('YYYY-MM-DD'), reference: '' }
  payDialogVisible.value = true
}

async function handlePay() {
  await payFormRef.value.validate()
  paying.value = true
  try {
    await paymentsApi.create({ ...payForm.value, bill_id: selectedBill.value.id })
    ElMessage.success('收款登錄成功')
    payDialogVisible.value = false
    fetchBills()
  } finally {
    paying.value = false
  }
}

async function sendNotice(bill) {
  await billsApi.sendNotice(bill.id)
  ElMessage.success('通知已發送')
}

const statusType  = (s) => ({ unpaid: 'warning', paid: 'success', overdue: 'danger' }[s])
const statusLabel = (s) => ({ unpaid: '未繳', paid: '已繳', overdue: '逾期' }[s])
</script>

<style scoped>
.card-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; }
.header-actions { display: flex; gap: 8px; align-items: center; }
.pagination { margin-top: 16px; display: flex; justify-content: flex-end; }
</style>
