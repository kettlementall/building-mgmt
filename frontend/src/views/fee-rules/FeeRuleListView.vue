<template>
  <el-card shadow="never">
    <template #header>
      <div class="card-header">
        <span>管理費規則</span>
        <el-button type="primary" @click="openDialog()">新增規則</el-button>
      </div>
    </template>

    <el-table :data="list" v-loading="loading" stripe>
      <el-table-column label="計費方式" width="130">
        <template #default="{ row }">
          <el-tag :type="row.type === 'fixed' ? '' : 'success'" size="small">
            {{ row.type === 'fixed' ? '固定金額' : '按坪數' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="金額 / 單價" width="160">
        <template #default="{ row }">
          NT$ {{ Number(row.amount).toLocaleString() }}
          <span v-if="row.type === 'per_area'" style="color:#909399"> / 坪</span>
        </template>
      </el-table-column>
      <el-table-column prop="effective_from" label="生效日" width="130" />
      <el-table-column label="結束日" width="130">
        <template #default="{ row }">{{ row.effective_to ?? '持續有效' }}</template>
      </el-table-column>
      <el-table-column prop="note" label="備註" />
      <el-table-column label="操作" width="140" fixed="right">
        <template #default="{ row }">
          <el-button size="small" @click="openDialog(row)">編輯</el-button>
          <el-button size="small" type="danger" @click="handleDelete(row)">刪除</el-button>
        </template>
      </el-table-column>
    </el-table>
  </el-card>

  <el-dialog v-model="dialogVisible" :title="editTarget ? '編輯規則' : '新增規則'" width="440px">
    <el-form :model="form" :rules="rules" ref="formRef" label-width="100px">
      <el-form-item label="計費方式" prop="type">
        <el-radio-group v-model="form.type">
          <el-radio value="fixed">固定金額</el-radio>
          <el-radio value="per_area">按坪數計算</el-radio>
        </el-radio-group>
      </el-form-item>
      <el-form-item :label="form.type === 'fixed' ? '管理費金額' : '每坪單價'" prop="amount">
        <el-input-number v-model="form.amount" :min="0" style="width:100%" />
      </el-form-item>
      <el-form-item label="生效日" prop="effective_from">
        <el-date-picker v-model="form.effective_from" type="date" value-format="YYYY-MM-DD" style="width:100%" />
      </el-form-item>
      <el-form-item label="結束日">
        <el-date-picker v-model="form.effective_to" type="date" value-format="YYYY-MM-DD" style="width:100%" clearable placeholder="不填表示持續有效" />
      </el-form-item>
      <el-form-item label="備註">
        <el-input v-model="form.note" type="textarea" :rows="2" />
      </el-form-item>
    </el-form>
    <template #footer>
      <el-button @click="dialogVisible = false">取消</el-button>
      <el-button type="primary" :loading="saving" @click="handleSave">儲存</el-button>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ElMessageBox, ElMessage } from 'element-plus'
import { feeRules as feeRulesApi } from '@/api'
import dayjs from 'dayjs'

const loading = ref(false)
const list    = ref([])

const dialogVisible = ref(false)
const editTarget    = ref(null)
const saving        = ref(false)
const formRef       = ref(null)
const form          = ref({ type: 'fixed', amount: 0, effective_from: dayjs().format('YYYY-MM-DD'), effective_to: null, note: '' })

const rules = {
  type:           [{ required: true, message: '請選擇計費方式' }],
  amount:         [{ required: true, type: 'number', min: 1, message: '請輸入金額' }],
  effective_from: [{ required: true, message: '請選擇生效日' }],
}

onMounted(fetchList)

async function fetchList() {
  loading.value = true
  list.value    = await feeRulesApi.list()
  loading.value = false
}

function openDialog(row = null) {
  editTarget.value = row
  form.value = row
    ? { type: row.type, amount: Number(row.amount), effective_from: row.effective_from, effective_to: row.effective_to, note: row.note ?? '' }
    : { type: 'fixed', amount: 0, effective_from: dayjs().format('YYYY-MM-DD'), effective_to: null, note: '' }
  dialogVisible.value = true
}

async function handleSave() {
  await formRef.value.validate()
  saving.value = true
  try {
    editTarget.value
      ? await feeRulesApi.update(editTarget.value.id, form.value)
      : await feeRulesApi.create(form.value)
    ElMessage.success('儲存成功')
    dialogVisible.value = false
    fetchList()
  } finally {
    saving.value = false
  }
}

async function handleDelete(row) {
  await ElMessageBox.confirm('確定刪除此規則？', '刪除確認', { type: 'warning' })
  await feeRulesApi.remove(row.id)
  ElMessage.success('已刪除')
  fetchList()
}
</script>

<style scoped>
.card-header { display: flex; justify-content: space-between; align-items: center; }
</style>
