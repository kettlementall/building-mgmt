<template>
  <el-card shadow="never">
    <template #header>
      <div class="card-header">
        <span>支出管理</span>
        <el-button type="primary" @click="openDialog()">新增支出</el-button>
      </div>
    </template>

    <!-- 篩選列 -->
    <el-row :gutter="12" style="margin-bottom:16px">
      <el-col :span="5">
        <el-select v-model="filter.year" placeholder="年份" style="width:100%" @change="fetchList">
          <el-option v-for="y in yearOptions" :key="y" :label="`${y} 年`" :value="y" />
        </el-select>
      </el-col>
      <el-col :span="5">
        <el-select v-model="filter.month" placeholder="月份" clearable style="width:100%" @change="fetchList">
          <el-option v-for="m in 12" :key="m" :label="`${m} 月`" :value="m" />
        </el-select>
      </el-col>
      <el-col :span="6">
        <el-select v-model="filter.category_id" placeholder="分類" clearable style="width:100%" @change="fetchList">
          <el-option v-for="c in categories" :key="c.id" :label="c.name" :value="c.id" />
        </el-select>
      </el-col>
    </el-row>

    <el-table :data="list" v-loading="loading" stripe>
      <el-table-column prop="expense_date" label="日期" width="120" sortable />
      <el-table-column label="分類" width="120">
        <template #default="{ row }">
          <el-tag :color="row.category?.color" size="small">{{ row.category?.name }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="title"  label="項目名稱" />
      <el-table-column prop="vendor" label="廠商" width="120" />
      <el-table-column label="金額" width="130">
        <template #default="{ row }">NT$ {{ Number(row.amount).toLocaleString() }}</template>
      </el-table-column>
      <el-table-column label="附件" width="80">
        <template #default="{ row }">
          <el-badge :value="row.attachments?.length" v-if="row.attachments?.length" type="info">
            <el-icon><Paperclip /></el-icon>
          </el-badge>
          <span v-else>-</span>
        </template>
      </el-table-column>
      <el-table-column label="操作" width="160" fixed="right">
        <template #default="{ row }">
          <el-button size="small" @click="openDialog(row)">編輯</el-button>
          <el-button size="small" type="primary" plain @click="openAttachDialog(row)">附件</el-button>
          <el-button size="small" type="danger" @click="handleDelete(row)">刪除</el-button>
        </template>
      </el-table-column>
    </el-table>

    <div class="footer-bar">
      <span class="total-label">本期支出合計：<strong>NT$ {{ totalAmount.toLocaleString() }}</strong></span>
      <el-pagination v-model:current-page="page" :total="total" :page-size="20"
        layout="total, prev, pager, next" @current-change="fetchList" />
    </div>
  </el-card>

  <!-- 新增/編輯 Dialog -->
  <el-dialog v-model="dialogVisible" :title="editTarget ? '編輯支出' : '新增支出'" width="480px">
    <el-form :model="form" :rules="rules" ref="formRef" label-width="90px">
      <el-form-item label="分類" prop="category_id">
        <el-select v-model="form.category_id" style="width:100%">
          <el-option v-for="c in categories" :key="c.id" :label="c.name" :value="c.id" />
        </el-select>
      </el-form-item>
      <el-form-item label="項目名稱" prop="title">
        <el-input v-model="form.title" />
      </el-form-item>
      <el-form-item label="金額" prop="amount">
        <el-input-number v-model="form.amount" :min="0" style="width:100%" />
      </el-form-item>
      <el-form-item label="支出日期" prop="expense_date">
        <el-date-picker v-model="form.expense_date" type="date" value-format="YYYY-MM-DD" style="width:100%" />
      </el-form-item>
      <el-form-item label="廠商">
        <el-input v-model="form.vendor" />
      </el-form-item>
      <el-form-item label="說明">
        <el-input v-model="form.description" type="textarea" :rows="2" />
      </el-form-item>
    </el-form>
    <template #footer>
      <el-button @click="dialogVisible = false">取消</el-button>
      <el-button type="primary" :loading="saving" @click="handleSave">儲存</el-button>
    </template>
  </el-dialog>

  <!-- 附件管理 Dialog -->
  <el-dialog v-model="attachDialogVisible" title="附件管理" width="480px">
    <div v-if="attachTarget">
      <div v-for="att in attachTarget.attachments" :key="att.id" class="attach-row">
        <el-link :href="att.url" target="_blank">{{ att.filename }}</el-link>
        <el-button size="small" type="danger" text @click="deleteAttachment(att)">刪除</el-button>
      </div>
      <el-divider v-if="attachTarget.attachments?.length" />
      <el-upload
        :action="`/api/expenses/${attachTarget.id}/attachments`"
        :headers="uploadHeaders"
        name="file"
        accept=".jpg,.jpeg,.png,.pdf"
        :on-success="onUploadSuccess"
        :on-error="onUploadError"
        :show-file-list="false"
      >
        <el-button type="primary" plain>上傳附件（JPG/PNG/PDF，最大 5MB）</el-button>
      </el-upload>
    </div>
  </el-dialog>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { ElMessageBox, ElMessage } from 'element-plus'
import { expenses as expensesApi, expenseCategories as categoriesApi } from '@/api'
import dayjs from 'dayjs'

const loading  = ref(false)
const list     = ref([])
const total    = ref(0)
const page     = ref(1)
const categories = ref([])

const filter = ref({ year: dayjs().year(), month: null, category_id: null })
const yearOptions = Array.from({ length: 5 }, (_, i) => dayjs().year() - i)

// 表單
const dialogVisible = ref(false)
const editTarget    = ref(null)
const saving        = ref(false)
const formRef       = ref(null)
const form          = ref({ category_id: null, title: '', amount: 0, expense_date: dayjs().format('YYYY-MM-DD'), vendor: '', description: '' })

const rules = {
  category_id:  [{ required: true, message: '請選擇分類' }],
  title:        [{ required: true, message: '請輸入項目名稱' }],
  amount:       [{ required: true, type: 'number', min: 1, message: '請輸入金額' }],
  expense_date: [{ required: true, message: '請選擇日期' }],
}

// 附件
const attachDialogVisible = ref(false)
const attachTarget        = ref(null)
const uploadHeaders       = computed(() => ({ Authorization: `Bearer ${localStorage.getItem('token')}` }))

const totalAmount = computed(() => list.value.reduce((s, r) => s + Number(r.amount), 0))

onMounted(async () => {
  categories.value = await categoriesApi.list()
  fetchList()
})

async function fetchList() {
  loading.value = true
  const res = await expensesApi.list({ page: page.value, ...filter.value })
  list.value  = res.data
  total.value = res.total
  loading.value = false
}

function openDialog(row = null) {
  editTarget.value = row
  form.value = row
    ? { category_id: row.category_id, title: row.title, amount: Number(row.amount), expense_date: row.expense_date, vendor: row.vendor ?? '', description: row.description ?? '' }
    : { category_id: null, title: '', amount: 0, expense_date: dayjs().format('YYYY-MM-DD'), vendor: '', description: '' }
  dialogVisible.value = true
}

async function handleSave() {
  await formRef.value.validate()
  saving.value = true
  try {
    if (editTarget.value) {
      await expensesApi.update(editTarget.value.id, form.value)
    } else {
      await expensesApi.create(form.value)
    }
    ElMessage.success('儲存成功')
    dialogVisible.value = false
    fetchList()
  } finally {
    saving.value = false
  }
}

async function handleDelete(row) {
  await ElMessageBox.confirm(`確定刪除「${row.title}」？`, '刪除確認', { type: 'warning' })
  await expensesApi.remove(row.id)
  ElMessage.success('已刪除')
  fetchList()
}

function openAttachDialog(row) {
  attachTarget.value = row
  attachDialogVisible.value = true
}

async function deleteAttachment(att) {
  await ElMessageBox.confirm('確定刪除此附件？', '確認', { type: 'warning' })
  await expensesApi.deleteAttachment(attachTarget.value.id, att.id)
  attachTarget.value.attachments = attachTarget.value.attachments.filter((a) => a.id !== att.id)
  ElMessage.success('已刪除')
}

function onUploadSuccess(res) {
  attachTarget.value.attachments = [...(attachTarget.value.attachments ?? []), res]
  ElMessage.success('上傳成功')
}

function onUploadError() {
  ElMessage.error('上傳失敗')
}
</script>

<style scoped>
.card-header { display: flex; justify-content: space-between; align-items: center; }
.footer-bar { display: flex; justify-content: space-between; align-items: center; margin-top: 16px; }
.total-label { color: #606266; }
.attach-row { display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px solid #f0f0f0; }
</style>
