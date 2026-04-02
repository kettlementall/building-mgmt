<template>
  <el-card shadow="never">
    <template #header>
      <div class="card-header">
        <span>戶別管理</span>
        <el-button type="primary" @click="openDialog()">新增戶別</el-button>
      </div>
    </template>

    <el-table :data="store.list" v-loading="store.loading" stripe>
      <el-table-column prop="floor"  label="樓層" width="80" sortable />
      <el-table-column prop="number" label="室號" width="80" />
      <el-table-column prop="area"   label="坪數" width="100">
        <template #default="{ row }">{{ row.area }} 坪</template>
      </el-table-column>
      <el-table-column label="現住住戶">
        <template #default="{ row }">{{ row.active_resident?.name ?? '-' }}</template>
      </el-table-column>
      <el-table-column label="狀態" width="100">
        <template #default="{ row }">
          <el-tag :type="row.status === 'occupied' ? 'success' : 'info'" size="small">
            {{ row.status === 'occupied' ? '有住戶' : '空置' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="note" label="備註" />
      <el-table-column label="操作" width="160" fixed="right">
        <template #default="{ row }">
          <el-button size="small" @click="openDialog(row)">編輯</el-button>
          <el-button size="small" type="danger" @click="handleDelete(row)">刪除</el-button>
        </template>
      </el-table-column>
    </el-table>
  </el-card>

  <!-- 新增/編輯 Dialog -->
  <el-dialog v-model="dialogVisible" :title="editTarget ? '編輯戶別' : '新增戶別'" width="420px">
    <el-form :model="form" :rules="rules" ref="formRef" label-width="80px">
      <el-form-item label="樓層" prop="floor">
        <el-input v-model="form.floor" placeholder="例：3" />
      </el-form-item>
      <el-form-item label="室號" prop="number">
        <el-input v-model="form.number" placeholder="例：01" />
      </el-form-item>
      <el-form-item label="坪數" prop="area">
        <el-input-number v-model="form.area" :min="0" :precision="2" style="width:100%" />
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
import { useUnitsStore } from '@/stores/units'

const store   = useUnitsStore()
const formRef = ref(null)
const dialogVisible = ref(false)
const editTarget    = ref(null)
const saving        = ref(false)

const form = ref({ floor: '', number: '', area: 0, note: '' })

const rules = {
  floor:  [{ required: true, message: '請輸入樓層', trigger: 'blur' }],
  number: [{ required: true, message: '請輸入室號', trigger: 'blur' }],
  area:   [{ required: true, type: 'number', min: 0, message: '請輸入坪數', trigger: 'blur' }],
}

onMounted(() => store.fetchAll())

function openDialog(row = null) {
  editTarget.value = row
  form.value = row
    ? { floor: row.floor, number: row.number, area: parseFloat(row.area), note: row.note ?? '' }
    : { floor: '', number: '', area: 0, note: '' }
  dialogVisible.value = true
}

async function handleSave() {
  await formRef.value.validate()
  saving.value = true
  try {
    if (editTarget.value) {
      await store.update(editTarget.value.id, form.value)
    } else {
      await store.create(form.value)
    }
    ElMessage.success('儲存成功')
    dialogVisible.value = false
  } finally {
    saving.value = false
  }
}

async function handleDelete(row) {
  await ElMessageBox.confirm(`確定刪除 ${row.floor} 樓 ${row.number} 室？`, '刪除確認', { type: 'warning' })
  await store.remove(row.id)
  ElMessage.success('已刪除')
}
</script>

<style scoped>
.card-header { display: flex; justify-content: space-between; align-items: center; }
</style>
