<template>
  <el-card shadow="never">
    <template #header>
      <div class="card-header">
        <span>住戶管理</span>
        <el-button type="primary" @click="openDialog()">新增住戶</el-button>
      </div>
    </template>

    <el-table :data="list" v-loading="loading" stripe>
      <el-table-column label="戶別" width="120">
        <template #default="{ row }">{{ row.unit?.floor }} 樓 {{ row.unit?.number }} 室</template>
      </el-table-column>
      <el-table-column prop="name"  label="姓名"  width="120" />
      <el-table-column prop="phone" label="電話"  width="140" />
      <el-table-column prop="email" label="Email" />
      <el-table-column label="身分" width="90">
        <template #default="{ row }">
          <el-tag size="small" :type="row.type === 'owner' ? '' : 'info'">
            {{ row.type === 'owner' ? '業主' : '租戶' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="move_in_date" label="入住日" width="120" />
      <el-table-column label="狀態" width="90">
        <template #default="{ row }">
          <el-tag size="small" :type="row.is_active ? 'success' : 'info'">
            {{ row.is_active ? '居住中' : '已遷出' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="操作" width="140" fixed="right">
        <template #default="{ row }">
          <el-button size="small" @click="openDialog(row)">編輯</el-button>
          <el-button size="small" type="danger" @click="handleDelete(row)">刪除</el-button>
        </template>
      </el-table-column>
    </el-table>

    <div style="margin-top:16px;display:flex;justify-content:flex-end">
      <el-pagination v-model:current-page="page" :total="total" :page-size="20"
        layout="total, prev, pager, next" @current-change="fetchList" />
    </div>
  </el-card>

  <el-dialog v-model="dialogVisible" :title="editTarget ? '編輯住戶' : '新增住戶'" width="480px">
    <el-form :model="form" :rules="rules" ref="formRef" label-width="90px">
      <el-form-item label="戶別" prop="unit_id">
        <el-select v-model="form.unit_id" style="width:100%">
          <el-option v-for="u in units" :key="u.id" :label="`${u.floor}樓${u.number}室`" :value="u.id" />
        </el-select>
      </el-form-item>
      <el-form-item label="姓名" prop="name">
        <el-input v-model="form.name" />
      </el-form-item>
      <el-form-item label="電話">
        <el-input v-model="form.phone" />
      </el-form-item>
      <el-form-item label="Email">
        <el-input v-model="form.email" />
      </el-form-item>
      <el-form-item label="身分" prop="type">
        <el-select v-model="form.type" style="width:100%">
          <el-option label="業主" value="owner" />
          <el-option label="租戶" value="tenant" />
        </el-select>
      </el-form-item>
      <el-form-item label="入住日" prop="move_in_date">
        <el-date-picker v-model="form.move_in_date" type="date" value-format="YYYY-MM-DD" style="width:100%" />
      </el-form-item>
      <el-form-item label="遷出日">
        <el-date-picker v-model="form.move_out_date" type="date" value-format="YYYY-MM-DD" style="width:100%" />
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
import { residents as residentsApi, units as unitsApi } from '@/api'
import dayjs from 'dayjs'

const loading = ref(false)
const list    = ref([])
const total   = ref(0)
const page    = ref(1)
const units   = ref([])

const dialogVisible = ref(false)
const editTarget    = ref(null)
const saving        = ref(false)
const formRef       = ref(null)
const form          = ref({ unit_id: null, name: '', phone: '', email: '', type: 'owner', move_in_date: dayjs().format('YYYY-MM-DD'), move_out_date: null })

const rules = {
  unit_id:      [{ required: true, message: '請選擇戶別' }],
  name:         [{ required: true, message: '請輸入姓名' }],
  type:         [{ required: true, message: '請選擇身分' }],
  move_in_date: [{ required: true, message: '請選擇入住日' }],
}

onMounted(async () => {
  units.value = await unitsApi.list()
  fetchList()
})

async function fetchList() {
  loading.value = true
  const res = await residentsApi.list({ page: page.value })
  list.value  = res.data
  total.value = res.total
  loading.value = false
}

function openDialog(row = null) {
  editTarget.value = row
  form.value = row
    ? { unit_id: row.unit_id, name: row.name, phone: row.phone ?? '', email: row.email ?? '', type: row.type, move_in_date: row.move_in_date, move_out_date: row.move_out_date }
    : { unit_id: null, name: '', phone: '', email: '', type: 'owner', move_in_date: dayjs().format('YYYY-MM-DD'), move_out_date: null }
  dialogVisible.value = true
}

async function handleSave() {
  await formRef.value.validate()
  saving.value = true
  try {
    editTarget.value
      ? await residentsApi.update(editTarget.value.id, form.value)
      : await residentsApi.create(form.value)
    ElMessage.success('儲存成功')
    dialogVisible.value = false
    fetchList()
  } finally {
    saving.value = false
  }
}

async function handleDelete(row) {
  await ElMessageBox.confirm(`確定刪除住戶「${row.name}」？`, '刪除確認', { type: 'warning' })
  await residentsApi.remove(row.id)
  ElMessage.success('已刪除')
  fetchList()
}
</script>

<style scoped>
.card-header { display: flex; justify-content: space-between; align-items: center; }
</style>
