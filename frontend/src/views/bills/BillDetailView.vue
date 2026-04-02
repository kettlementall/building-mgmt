<template>
  <div v-if="bill">
    <el-page-header @back="$router.back()" :content="`帳單詳情 — ${bill.year}/${String(bill.month).padStart(2,'0')}`" style="margin-bottom:16px" />

    <el-row :gutter="16">
      <el-col :span="12">
        <el-card shadow="never" header="帳單資訊">
          <el-descriptions :column="1" border>
            <el-descriptions-item label="戶別">{{ bill.unit?.floor }} 樓 {{ bill.unit?.number }} 室</el-descriptions-item>
            <el-descriptions-item label="住戶">{{ bill.unit?.active_resident?.name ?? '-' }}</el-descriptions-item>
            <el-descriptions-item label="坪數">{{ bill.unit?.area }} 坪</el-descriptions-item>
            <el-descriptions-item label="計費方式">
              {{ bill.fee_rule?.type === 'fixed' ? '固定金額' : `按坪數 (NT$ ${bill.fee_rule?.amount}/坪)` }}
            </el-descriptions-item>
            <el-descriptions-item label="應繳金額">
              <strong style="font-size:18px;color:#409eff">NT$ {{ Number(bill.amount).toLocaleString() }}</strong>
            </el-descriptions-item>
            <el-descriptions-item label="截止日">{{ bill.due_date }}</el-descriptions-item>
            <el-descriptions-item label="狀態">
              <el-tag :type="statusType(bill.status)">{{ statusLabel(bill.status) }}</el-tag>
            </el-descriptions-item>
            <el-descriptions-item label="備註">{{ bill.note ?? '-' }}</el-descriptions-item>
          </el-descriptions>
        </el-card>
      </el-col>

      <el-col :span="12">
        <el-card shadow="never" header="繳費紀錄">
          <div v-if="bill.payment">
            <el-descriptions :column="1" border>
              <el-descriptions-item label="繳費日期">{{ bill.payment.paid_at }}</el-descriptions-item>
              <el-descriptions-item label="繳費方式">{{ bill.payment.method === 'cash' ? '現金' : '匯款' }}</el-descriptions-item>
              <el-descriptions-item label="金額">NT$ {{ Number(bill.payment.amount).toLocaleString() }}</el-descriptions-item>
              <el-descriptions-item label="匯款單號">{{ bill.payment.reference ?? '-' }}</el-descriptions-item>
              <el-descriptions-item label="登錄人員">{{ bill.payment.recorder?.name }}</el-descriptions-item>
              <el-descriptions-item label="備註">{{ bill.payment.note ?? '-' }}</el-descriptions-item>
            </el-descriptions>
            <el-button type="danger" size="small" style="margin-top:12px" @click="cancelPayment">取消收款</el-button>
          </div>
          <el-empty v-else description="尚未收款">
            <el-button type="primary" @click="payDialogVisible = true">登錄收款</el-button>
          </el-empty>
        </el-card>
      </el-col>
    </el-row>
  </div>

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
        <el-input v-model="payForm.reference" />
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
import { useRoute } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { bills as billsApi, payments as paymentsApi } from '@/api'
import dayjs from 'dayjs'

const route = useRoute()
const bill  = ref(null)

const payDialogVisible = ref(false)
const paying           = ref(false)
const payFormRef       = ref(null)
const payForm          = ref({ amount: 0, method: 'cash', paid_at: dayjs().format('YYYY-MM-DD'), reference: '' })
const payRules         = {
  amount:  [{ required: true, type: 'number', min: 1 }],
  method:  [{ required: true }],
  paid_at: [{ required: true }],
}

onMounted(async () => {
  bill.value = await billsApi.get(route.params.id)
  payForm.value.amount = Number(bill.value.amount)
})

async function handlePay() {
  await payFormRef.value.validate()
  paying.value = true
  try {
    await paymentsApi.create({ ...payForm.value, bill_id: bill.value.id })
    ElMessage.success('收款登錄成功')
    payDialogVisible.value = false
    bill.value = await billsApi.get(route.params.id)
  } finally {
    paying.value = false
  }
}

async function cancelPayment() {
  await ElMessageBox.confirm('確定取消此筆收款紀錄？帳單將恢復為未繳狀態。', '確認', { type: 'warning' })
  await paymentsApi.remove(bill.value.payment.id)
  ElMessage.success('已取消收款')
  bill.value = await billsApi.get(route.params.id)
}

const statusType  = (s) => ({ unpaid: 'warning', paid: 'success', overdue: 'danger' }[s])
const statusLabel = (s) => ({ unpaid: '未繳', paid: '已繳', overdue: '逾期' }[s])
</script>
