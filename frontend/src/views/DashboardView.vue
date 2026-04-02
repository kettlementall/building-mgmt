<template>
  <div>
    <el-row :gutter="16" class="stat-row">
      <el-col :span="6" v-for="stat in stats" :key="stat.label">
        <el-card shadow="never" class="stat-card">
          <div class="stat-value" :style="{ color: stat.color }">{{ stat.value }}</div>
          <div class="stat-label">{{ stat.label }}</div>
        </el-card>
      </el-col>
    </el-row>

    <el-row :gutter="16" style="margin-top:16px">
      <el-col :span="14">
        <el-card shadow="never" header="本年度收支總覽">
          <v-chart :option="barOption" style="height:300px" autoresize />
        </el-card>
      </el-col>
      <el-col :span="10">
        <el-card shadow="never" header="支出分類佔比">
          <v-chart :option="pieOption" style="height:300px" autoresize />
        </el-card>
      </el-col>
    </el-row>

    <el-card shadow="never" header="近期未繳帳單" style="margin-top:16px">
      <el-table :data="overdueList" size="small">
        <el-table-column prop="unit.floor" label="樓層" width="80" />
        <el-table-column prop="unit.number" label="室號" width="80" />
        <el-table-column label="住戶" width="120">
          <template #default="{ row }">{{ row.unit?.active_resident?.name ?? '-' }}</template>
        </el-table-column>
        <el-table-column label="年月" width="100">
          <template #default="{ row }">{{ row.year }}/{{ String(row.month).padStart(2,'0') }}</template>
        </el-table-column>
        <el-table-column prop="amount" label="金額" width="100">
          <template #default="{ row }">NT$ {{ row.amount.toLocaleString() }}</template>
        </el-table-column>
        <el-table-column prop="due_date" label="截止日" />
        <el-table-column label="狀態" width="90">
          <template #default="{ row }">
            <el-tag :type="row.status === 'overdue' ? 'danger' : 'warning'" size="small">
              {{ row.status === 'overdue' ? '逾期' : '未繳' }}
            </el-tag>
          </template>
        </el-table-column>
      </el-table>
    </el-card>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import VChart from 'vue-echarts'
import { use } from 'echarts/core'
import { BarChart, PieChart } from 'echarts/charts'
import { TitleComponent, TooltipComponent, LegendComponent, GridComponent } from 'echarts/components'
import { CanvasRenderer } from 'echarts/renderers'
import { reports, bills } from '@/api'
import dayjs from 'dayjs'

use([BarChart, PieChart, TitleComponent, TooltipComponent, LegendComponent, GridComponent, CanvasRenderer])

const year        = dayjs().year()
const balanceData = ref(null)
const expenseData = ref(null)
const overdueList = ref([])

onMounted(async () => {
  const [bd, ed, od] = await Promise.all([
    reports.balance({ year }),
    reports.expense({ year }),
    reports.overdue({}),
  ])
  balanceData.value = bd
  expenseData.value = ed
  overdueList.value = od.bills.slice(0, 10)
})

const stats = computed(() => {
  const b = balanceData.value
  return [
    { label: '本年收入', value: b ? `NT$ ${b.total_income.toLocaleString()}` : '-', color: '#67c23a' },
    { label: '本年支出', value: b ? `NT$ ${b.total_expense.toLocaleString()}` : '-', color: '#e6a23c' },
    { label: '本年結餘', value: b ? `NT$ ${b.total_balance.toLocaleString()}` : '-', color: '#409eff' },
    { label: '未繳筆數', value: overdueList.value.length, color: '#f56c6c' },
  ]
})

const barOption = computed(() => ({
  tooltip: { trigger: 'axis' },
  legend: { data: ['收入', '支出'] },
  xAxis: { type: 'category', data: balanceData.value?.months.map((m) => `${m.month}月`) ?? [] },
  yAxis: { type: 'value' },
  series: [
    { name: '收入', type: 'bar', data: balanceData.value?.months.map((m) => m.income) ?? [], itemStyle: { color: '#67c23a' } },
    { name: '支出', type: 'bar', data: balanceData.value?.months.map((m) => m.expense) ?? [], itemStyle: { color: '#e6a23c' } },
  ],
}))

const pieOption = computed(() => ({
  tooltip: { trigger: 'item', formatter: '{b}: NT$ {c} ({d}%)' },
  series: [{
    type: 'pie',
    radius: ['40%', '70%'],
    data: expenseData.value?.by_category.map((c) => ({ name: c.category, value: c.total })) ?? [],
  }],
}))
</script>

<style scoped>
.stat-row { margin-bottom: 0; }
.stat-card { text-align: center; padding: 8px 0; }
.stat-value { font-size: 26px; font-weight: bold; }
.stat-label { color: #909399; margin-top: 4px; }
</style>
